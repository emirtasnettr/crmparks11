<?php

namespace App\Modules\Business\Services;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Finance\Services\EarningFinanceSyncService;
use App\Modules\Notification\Services\EarningNotificationService;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Setting\Services\SettingsManager;
use App\Support\EarningCalculator;
use App\Support\EarningStatusMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BusinessEarningService
{
    public function __construct(
        private readonly BusinessEarningPresenter $presenter,
        private readonly ActivityLogService $activityLog,
        private readonly EarningFinanceSyncService $earningFinanceSync,
        private readonly EarningNotificationService $earningNotifications,
        private readonly SettingsManager $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EarningLine>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['business', 'courier.agency', 'status'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, EarningLine>
     */
    public function forBusiness(int $businessId): Collection
    {
        return EarningLine::query()
            ->where('business_id', $businessId)
            ->with(['business', 'courier.agency', 'status'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();
    }

    public function find(int $id): ?EarningLine
    {
        return EarningLine::query()
            ->with(['business', 'courier.agency', 'status', 'creator'])
            ->find($id);
    }

    /**
     * @param  Collection<int, EarningLine>  $items
     * @return array<string, float|int>
     */
    public function summarize(Collection $items): array
    {
        $rows = $items->map(fn (EarningLine $line) => $this->presenter->indexRow($line));

        return [
            'count' => $rows->count(),
            'total_revenue' => round($rows->sum('revenue'), 2),
            'total_expense' => round($rows->sum('total_expense'), 2),
            'total_profit' => round($rows->sum('profit'), 2),
            'pending_count' => $rows->where('status', 'pending')->count(),
            'paid_count' => $rows->where('status', 'paid')->count(),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function businesses(): array
    {
        return Business::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->displayName(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string, courier_type: string, agency_id: int|null}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone', 'courier_type', 'agency_id'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
                'phone' => $courier->phone ?? '—',
                'courier_type' => $courier->courier_type ?? 'independent',
                'agency_id' => $courier->agency_id,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('brand_name')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'brand_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->displayName(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): EarningLine
    {
        return DB::transaction(function () use ($data, $user): EarningLine {
            $courier = Courier::query()->findOrFail((int) $data['courier_id']);
            $statusCode = EarningStatusMapper::toStorageCode($data['status'] ?? 'draft');
            $statusId = EarningStatus::query()->where('code', $statusCode)->value('id')
                ?? EarningStatus::query()->where('code', 'draft')->value('id');

            $amounts = EarningCalculator::fromForm($data, $courier->agency_id !== null);
            $paidAt = ($data['status'] ?? 'draft') === 'paid' ? now() : null;
            $workDate = $this->resolveWorkDate($data);

            $line = EarningLine::query()->create(array_merge($amounts, [
                'business_id' => (int) $data['business_id'],
                'courier_id' => $courier->id,
                'pricing_model' => $data['pricing_model'] ?? 'per_package',
                'work_date' => $workDate->toDateString(),
                'period_month' => (int) $workDate->month,
                'period_year' => (int) $workDate->year,
                'description' => $data['description'] ?? null,
                'status_id' => $statusId,
                'paid_at' => $paidAt,
                'created_by' => $user->id,
            ]));

            $this->activityLog->log(
                'earning_created',
                $line,
                description: $this->activityDescription($line, 'oluşturuldu'),
            );

            $line = $line->fresh(['business', 'courier.agency', 'status']);
            $this->earningNotifications->notifyCreated($line, $user);

            // Tekli / import ile girilen hakedişler onay beklemez; işletme ve kurye tarafında onaylı görünür.
            if (in_array($statusCode, ['draft', 'pending_review'], true)) {
                return $this->finalizeApproval($line, $user);
            }

            return $line;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, User $user): EarningLine
    {
        return DB::transaction(function () use ($id, $data, $user): EarningLine {
            $line = $this->find($id);

            if ($line === null) {
                abort(404);
            }

            if (! $this->canUpdate($line)) {
                throw ValidationException::withMessages([
                    'earning' => 'Bu hakediş düzenlenemez.',
                ]);
            }

            $courier = Courier::query()->findOrFail((int) $data['courier_id']);
            $amounts = EarningCalculator::fromForm($data, $courier->agency_id !== null);
            $workDate = $this->resolveWorkDate($data);
            $oldValues = $line->only([
                'business_id',
                'courier_id',
                'work_date',
                'period_month',
                'period_year',
                'pricing_model',
                'package_count',
                'revenue_total',
                'courier_total',
                'profit',
                'description',
            ]);

            $line->update(array_merge($amounts, [
                'business_id' => (int) $data['business_id'],
                'courier_id' => $courier->id,
                'pricing_model' => $data['pricing_model'] ?? 'per_package',
                'work_date' => $workDate->toDateString(),
                'period_month' => (int) $workDate->month,
                'period_year' => (int) $workDate->year,
                'description' => $data['description'] ?? null,
            ]));

            $line = $line->fresh(['business', 'courier.agency', 'status', 'creator']);

            $this->activityLog->log(
                'earning_updated',
                $line,
                oldValues: $oldValues,
                newValues: $line->only(array_keys($oldValues)),
                description: $this->activityDescription($line, 'güncellendi'),
            );

            return $line;
        });
    }

    public function approve(int $id, User $user): EarningLine
    {
        return DB::transaction(function () use ($id, $user): EarningLine {
            $line = $this->find($id);

            if ($line === null) {
                abort(404);
            }

            if (! $this->canApprove($line, $user)) {
                throw ValidationException::withMessages([
                    'earning' => $this->approveBlockedMessage($line, $user),
                ]);
            }

            if ($this->approvalProcess() === 'dual' && $line->first_approved_by === null) {
                return $this->recordFirstApproval($line, $user);
            }

            return $this->finalizeApproval($line, $user);
        });
    }

    public function delete(int $id, User $user): void
    {
        DB::transaction(function () use ($id, $user): void {
            $line = $this->find($id);

            if ($line === null) {
                abort(404);
            }

            if (! $this->canDelete($line)) {
                throw ValidationException::withMessages([
                    'earning' => 'Bu hakediş silinemez.',
                ]);
            }

            $this->activityLog->log(
                'earning_updated',
                $line,
                description: $this->activityDescription($line, 'silindi'),
            );

            $line->delete();
        });
    }

    public function canUpdate(EarningLine $line): bool
    {
        return ! in_array($this->statusCode($line), ['paid', 'cancelled'], true);
    }

    public function canApprove(EarningLine $line, ?User $user = null): bool
    {
        if (! in_array($this->statusCode($line), ['draft', 'pending_review'], true)) {
            return false;
        }

        if ($this->approvalProcess() !== 'dual' || $line->first_approved_by === null) {
            return true;
        }

        if ($user === null) {
            return true;
        }

        return (int) $line->first_approved_by !== (int) $user->id;
    }

    public function canDelete(EarningLine $line): bool
    {
        return $this->canUpdate($line);
    }

    public function statusCode(EarningLine $line): string
    {
        $line->loadMissing('status');

        return (string) ($line->status?->code ?? 'draft');
    }

    public function approvalProcess(): string
    {
        $process = $this->settings->group('earnings')->all()['approval_process'] ?? 'dual';

        return in_array($process, ['single', 'dual', 'auto'], true) ? $process : 'dual';
    }

    private function recordFirstApproval(EarningLine $line, User $user): EarningLine
    {
        $statusId = EarningStatus::query()->where('code', 'pending_review')->value('id');

        if ($statusId === null) {
            abort(500, 'Bekleyen onay durumu bulunamadı.');
        }

        $line->update([
            'status_id' => $statusId,
            'first_approved_by' => $user->id,
            'first_approved_at' => now(),
        ]);

        $line = $line->fresh(['business', 'courier.agency', 'status', 'creator', 'firstApprover']);

        $this->activityLog->log(
            'earning_updated',
            $line,
            description: $this->activityDescription($line, 'ilk onayı alındı'),
        );

        return $line;
    }

    private function finalizeApproval(EarningLine $line, User $user): EarningLine
    {
        $statusId = EarningStatus::query()->where('code', 'approved')->value('id');

        if ($statusId === null) {
            abort(500, 'Onay durumu bulunamadı.');
        }

        $line->update([
            'status_id' => $statusId,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $line = $line->fresh(['business', 'courier.agency', 'status', 'creator']);

        $this->earningFinanceSync->syncOnApprove($line, $user);

        $this->activityLog->log(
            'earning_updated',
            $line,
            description: $this->activityDescription($line, 'onaylandı'),
        );

        $this->earningNotifications->notifyApproved($line, $user);

        return $line;
    }

    private function approveBlockedMessage(EarningLine $line, User $user): string
    {
        if (
            $this->approvalProcess() === 'dual'
            && $line->first_approved_by !== null
            && (int) $line->first_approved_by === (int) $user->id
        ) {
            return 'Çift onay sürecinde ikinci onayı farklı bir kullanıcı vermelidir.';
        }

        return 'Bu hakediş onaylanamaz.';
    }

    private function activityDescription(EarningLine $line, string $action): string
    {
        $line->loadMissing(['business', 'courier']);

        $period = $line->work_date
            ? $line->work_date->format('d.m.Y')
            : sprintf('%02d/%d', $line->period_month, $line->period_year);
        $business = $line->business?->displayName() ?? 'İşletme';
        $courier = $line->courier?->full_name ?? 'Kurye';

        return "{$business} / {$courier} ({$period}) hakedişi {$action}.";
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        return EarningLine::query()
            ->when(! empty($filters['business_id']) && $filters['business_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('business_id', (int) $filters['business_id']);
            })
            ->when(! empty($filters['courier_id']) && $filters['courier_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('courier_id', (int) $filters['courier_id']);
            })
            ->when(! empty($filters['agency_id']) && $filters['agency_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->whereHas('courier', fn (Builder $inner) => $inner->where('agency_id', (int) $filters['agency_id']));
            })
            ->when(! empty($filters['period_month']) && $filters['period_month'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('period_month', (int) $filters['period_month']);
            })
            ->when(! empty($filters['period_year']) && $filters['period_year'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('period_year', (int) $filters['period_year']);
            })
            ->when(! empty($filters['status']) && $filters['status'] !== 'all', function (Builder $query) use ($filters): void {
                $storageCode = EarningStatusMapper::toStorageCode($filters['status']);
                $query->whereHas('status', fn (Builder $inner) => $inner->where('code', $storageCode));
            })
            ->when(
                Schema::hasColumn('earning_lines', 'pricing_model')
                && ! empty($filters['pricing_model'])
                && $filters['pricing_model'] !== 'all',
                function (Builder $query) use ($filters): void {
                    $query->where('pricing_model', $filters['pricing_model']);
                },
            );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveWorkDate(array $data): \Carbon\Carbon
    {
        if (! empty($data['work_date'])) {
            return \Carbon\Carbon::parse((string) $data['work_date'])->startOfDay();
        }

        $year = (int) ($data['period_year'] ?? now()->year);
        $month = (int) ($data['period_month'] ?? now()->month);

        return \Carbon\Carbon::create($year, $month, 1)->startOfDay();
    }
}
