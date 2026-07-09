<?php

namespace App\Modules\Business\Services;

use App\Models\EarningLine;
use App\Models\EarningStatus;
use App\Models\User;
use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Support\EarningCalculator;
use App\Support\EarningStatusMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BusinessEarningService
{
    public function __construct(
        private readonly BusinessEarningPresenter $presenter,
        private readonly BusinessAssignmentService $assignments,
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

            return EarningLine::query()->create(array_merge($amounts, [
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
        });
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
