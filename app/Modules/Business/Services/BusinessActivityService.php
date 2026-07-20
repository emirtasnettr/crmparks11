<?php

namespace App\Modules\Business\Services;

use App\Models\Contract;
use App\Models\Document;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Business\Data\BusinessActivityFormData;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Models\BusinessContact;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BusinessActivityService
{
    public function __construct(
        private readonly BusinessActivityPresenter $presenter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ActivityLog>
     */
    public function filter(array $filters): Collection
    {
        $logs = $this->baseQuery($filters)
            ->with(['user', 'subject'])
            ->orderByDesc('created_at')
            ->get();

        return $this->applyBusinessFilter($logs, $filters);
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function forBusiness(int $businessId): Collection
    {
        return $this->filter(['business_id' => $businessId]);
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
     * @return array<int, array{id: int, name: string}>
     */
    public function users(): array
    {
        return User::query()
            ->whereIn('id', $this->businessActivityQuery()->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $today = Carbon::today();

        return $this->businessActivityQuery()
            ->when(! empty($filters['user_id']) && $filters['user_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('user_id', (int) $filters['user_id']);
            })
            ->when(! empty($filters['action']) && $filters['action'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('action', $filters['action']);
            })
            ->when(! empty($filters['date_range']) && $filters['date_range'] !== 'all', function (Builder $query) use ($filters, $today): void {
                match ($filters['date_range']) {
                    'last_7_days' => $query->where('created_at', '>=', $today->copy()->subDays(7)),
                    'last_30_days' => $query->where('created_at', '>=', $today->copy()->subDays(30)),
                    'this_month' => $query
                        ->whereMonth('created_at', $today->month)
                        ->whereYear('created_at', $today->year),
                    'last_3_months' => $query->where('created_at', '>=', $today->copy()->subMonths(3)),
                    'this_year' => $query->whereYear('created_at', $today->year),
                    default => null,
                };
            });
    }

    private function businessActivityQuery(): Builder
    {
        $businessDocumentIds = Document::query()
            ->where('documentable_type', Business::class)
            ->pluck('id');

        $businessContractIds = Contract::query()
            ->where('contractable_type', Business::class)
            ->pluck('id');

        return ActivityLog::query()
            ->whereIn('action', array_keys(BusinessActivityFormData::actionTypes()))
            ->where(function (Builder $query) use ($businessDocumentIds, $businessContractIds): void {
                $query->whereIn('subject_type', [
                    Business::class,
                    BusinessContact::class,
                    EarningLine::class,
                    FinanceRevenue::class,
                    FinanceCollection::class,
                ]);

                if ($businessDocumentIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $inner) use ($businessDocumentIds): void {
                        $inner->where('subject_type', Document::class)
                            ->whereIn('subject_id', $businessDocumentIds);
                    });
                }

                if ($businessContractIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $inner) use ($businessContractIds): void {
                        $inner->where('subject_type', Contract::class)
                            ->whereIn('subject_id', $businessContractIds);
                    });
                }
            });
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ActivityLog>
     */
    private function applyBusinessFilter(Collection $logs, array $filters): Collection
    {
        if (empty($filters['business_id']) || $filters['business_id'] === 'all') {
            return $logs;
        }

        $businessId = (int) $filters['business_id'];

        return $logs
            ->filter(fn (ActivityLog $log) => $this->presenter->resolveBusinessId($log) === $businessId)
            ->values();
    }
}
