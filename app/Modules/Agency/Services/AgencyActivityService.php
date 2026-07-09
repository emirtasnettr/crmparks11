<?php

namespace App\Modules\Agency\Services;

use App\Models\Contract;
use App\Models\Document;
use App\Models\EarningLine;
use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Agency\Data\AgencyActivityFormData;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyContact;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Models\FinanceExpense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AgencyActivityService
{
    public function __construct(
        private readonly AgencyActivityPresenter $presenter,
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
            ->get()
            ->filter(fn (ActivityLog $log) => $this->presenter->resolveAgencyId($log) !== null)
            ->values();

        return $this->applyAgencyFilter($logs, $filters);
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function forAgency(int $agencyId): Collection
    {
        return $this->filter(['agency_id' => $agencyId]);
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $items = $this->filter([])->map(fn (ActivityLog $log) => $this->presenter->indexRow($log));
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();

        return [
            'count' => $items->count(),
            'today' => $items->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->isSameDay($today))->count(),
            'this_week' => $items->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->gte($weekStart))->count(),
            'this_month' => $items->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->isSameMonth($today))->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(array $filters): array
    {
        return $this->filter($filters)
            ->map(fn (ActivityLog $log) => $this->presenter->indexRow($log))
            ->values()
            ->all();
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
     * @return array<int, array{id: int, name: string}>
     */
    public function users(): array
    {
        return User::query()
            ->whereIn('id', $this->agencyActivityQuery()->distinct()->pluck('user_id'))
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
        $weekStart = $today->copy()->startOfWeek();

        return $this->agencyActivityQuery()
            ->when(! empty($filters['user_id']) && $filters['user_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('user_id', (int) $filters['user_id']);
            })
            ->when(! empty($filters['action']) && $filters['action'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('action', $filters['action']);
            })
            ->when(! empty($filters['date_range']) && $filters['date_range'] !== 'all', function (Builder $query) use ($filters, $today, $weekStart): void {
                match ($filters['date_range']) {
                    'today' => $query->whereDate('created_at', $today),
                    'this_week' => $query->where('created_at', '>=', $weekStart),
                    'this_month' => $query
                        ->whereMonth('created_at', $today->month)
                        ->whereYear('created_at', $today->year),
                    'last_7_days' => $query->where('created_at', '>=', $today->copy()->subDays(7)),
                    'last_30_days' => $query->where('created_at', '>=', $today->copy()->subDays(30)),
                    'this_year' => $query->whereYear('created_at', $today->year),
                    default => null,
                };
            });
    }

    private function agencyActivityQuery(): Builder
    {
        $agencyDocumentIds = Document::query()
            ->where('documentable_type', Agency::class)
            ->pluck('id');

        $agencyContractIds = Contract::query()
            ->where('contractable_type', Agency::class)
            ->pluck('id');

        return ActivityLog::query()
            ->whereIn('action', array_keys(AgencyActivityFormData::actionTypes()))
            ->where(function (Builder $query) use ($agencyDocumentIds, $agencyContractIds): void {
                $query->whereIn('subject_type', [
                    Agency::class,
                    AgencyContact::class,
                    Courier::class,
                    FinanceExpense::class,
                    EarningLine::class,
                ]);

                if ($agencyDocumentIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $inner) use ($agencyDocumentIds): void {
                        $inner->where('subject_type', Document::class)
                            ->whereIn('subject_id', $agencyDocumentIds);
                    });
                }

                if ($agencyContractIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $inner) use ($agencyContractIds): void {
                        $inner->where('subject_type', Contract::class)
                            ->whereIn('subject_id', $agencyContractIds);
                    });
                }
            });
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ActivityLog>
     */
    private function applyAgencyFilter(Collection $logs, array $filters): Collection
    {
        if (empty($filters['agency_id']) || $filters['agency_id'] === 'all') {
            return $logs;
        }

        $agencyId = (int) $filters['agency_id'];

        return $logs
            ->filter(fn (ActivityLog $log) => $this->presenter->resolveAgencyId($log) === $agencyId)
            ->values();
    }
}
