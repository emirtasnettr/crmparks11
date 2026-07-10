<?php

namespace App\Modules\Business\Services;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
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
        private readonly BusinessAssignmentService $assignments,
        private readonly ActivityLogService $activityLog,
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
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->company_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string, courier_type: string, agency_id: int|null}>
     */
    public function couriers(): array
    {
        return $this->assignments->couriers();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function agencies(): array
    {
        return Agency::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'name' => $agency->company_name,
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

            $line = EarningLine::query()->create(array_merge($amounts, [
                'business_id' => (int) $data['business_id'],
                'courier_id' => $courier->id,
                'pricing_model' => $data['pricing_model'] ?? 'per_package',
                'period_month' => (int) $data['period_month'],
                'period_year' => (int) $data['period_year'],
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
            $oldValues = $line->only([
                'business_id',
                'courier_id',
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
                'period_month' => (int) $data['period_month'],
                'period_year' => (int) $data['period_year'],
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

            if (! $this->canApprove($line)) {
                throw ValidationException::withMessages([
                    'earning' => 'Bu hakediş onaylanamaz.',
                ]);
            }

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

            $this->activityLog->log(
                'earning_updated',
                $line,
                description: $this->activityDescription($line, 'onaylandı'),
            );

            return $line;
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

    public function canApprove(EarningLine $line): bool
    {
        return in_array($this->statusCode($line), ['draft', 'pending_review'], true);
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

    private function activityDescription(EarningLine $line, string $action): string
    {
        $line->loadMissing(['business', 'courier']);

        $period = sprintf('%02d/%d', $line->period_month, $line->period_year);
        $business = $line->business?->company_name ?? 'İşletme';
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
}
