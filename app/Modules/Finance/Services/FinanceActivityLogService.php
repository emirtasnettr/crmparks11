<?php

namespace App\Modules\Finance\Services;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Finance\Data\FinanceActivityLogFormData;
use App\Modules\Finance\Models\CurrentAccount;
use App\Modules\Finance\Models\CurrentAccountMovement;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceInvoice;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinanceActivityLogService
{
    /**
     * @var array<int, class-string>
     */
    private const SUBJECT_TYPES = [
        FinanceRevenue::class,
        FinanceExpense::class,
        FinanceCollection::class,
        FinancePayment::class,
        FinanceInvoice::class,
        CurrentAccount::class,
        CurrentAccountMovement::class,
    ];

    public function __construct(
        private readonly FinanceActivityLogPresenter $presenter,
    ) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function analyze(array $filters, int $page = 1, int $perPage = 25): array
    {
        $allLogs = $this->financeLogs();
        $filteredLogs = $this->applyFilters($allLogs, $filters);
        $rows = $filteredLogs->map(fn (ActivityLog $log) => $this->presenter->indexRow($log))->values();

        $total = $rows->count();
        $items = $rows->slice(($page - 1) * $perPage, $perPage)->values()->all();

        $allRows = $allLogs->map(fn (ActivityLog $log) => $this->presenter->indexRow($log));

        $logsForModal = collect($items)
            ->mapWithKeys(function (array $row) use ($filteredLogs): array {
                $log = $filteredLogs->firstWhere('id', $row['id']);

                return [$row['id'] => $log ? $this->presenter->detailPayload($log) : []];
            })
            ->all();

        return [
            'logs' => $items,
            'summary' => $this->summarize($rows, $allRows),
            'logs_for_modal' => $logsForModal,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function filter(array $filters): array
    {
        return $this->applyFilters($this->financeLogs(), $filters)
            ->map(fn (ActivityLog $log) => $this->presenter->indexRow($log))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function users(): array
    {
        return User::query()
            ->whereIn('id', $this->financeQuery()->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function currentAccounts(): array
    {
        return $this->financeLogs()
            ->map(fn (ActivityLog $log) => $this->presenter->resolveCurrentAccountName($log))
            ->filter(fn (string $name) => $name !== '—')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    private function financeLogs(): Collection
    {
        return $this->financeQuery()
            ->with(['user', 'subject'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @param  array<string, string>  $filters
     * @return Collection<int, ActivityLog>
     */
    private function applyFilters(Collection $logs, array $filters): Collection
    {
        return $logs->filter(function (ActivityLog $log) use ($filters): bool {
            if (($filters['action_type'] ?? 'all') !== 'all' && $log->action !== $filters['action_type']) {
                return false;
            }

            if (($filters['module'] ?? 'all') !== 'all') {
                $module = FinanceActivityLogFormData::moduleForSubjectType($log->subject_type);
                if ($module !== $filters['module']) {
                    return false;
                }
            }

            if (($filters['user_id'] ?? 'all') !== 'all' && (int) $log->user_id !== (int) $filters['user_id']) {
                return false;
            }

            if (($filters['current_account'] ?? 'all') !== 'all') {
                if ($this->presenter->resolveCurrentAccountName($log) !== $filters['current_account']) {
                    return false;
                }
            }

            if (! empty($filters['reference'])) {
                $needle = mb_strtolower($filters['reference']);
                $haystack = mb_strtolower(
                    $this->presenter->resolveReference($log).' '.($log->description ?? '')
                );

                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            if (($filters['date_range'] ?? 'all') !== 'all' && ! $this->matchesDateRange($log, $filters['date_range'])) {
                return false;
            }

            return true;
        })->values();
    }

    private function financeQuery(): Builder
    {
        return ActivityLog::query()
            ->where(function (Builder $query): void {
                $query->whereIn('action', FinanceActivityLogFormData::financeActions())
                    ->orWhereIn('subject_type', self::SUBJECT_TYPES);
            });
    }

    private function matchesDateRange(ActivityLog $log, string $range): bool
    {
        $occurred = Carbon::parse($log->created_at);
        $today = Carbon::today();

        return match ($range) {
            'today' => $occurred->isSameDay($today),
            'week' => $occurred->between($today->copy()->startOfWeek(), $today->copy()->endOfWeek()),
            'month' => $occurred->month === $today->month && $occurred->year === $today->year,
            'year' => $occurred->year === $today->year,
            default => true,
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filtered
     * @param  Collection<int, array<string, mixed>>  $allRows
     * @return array<string, int>
     */
    private function summarize(Collection $filtered, Collection $allRows): array
    {
        $today = Carbon::today();

        return [
            'total' => $filtered->count(),
            'today' => $allRows->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->isSameDay($today))->count(),
            'this_week' => $allRows->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->between(
                $today->copy()->startOfWeek(),
                $today->copy()->endOfWeek()
            ))->count(),
            'this_month' => $allRows->filter(fn (array $row) => Carbon::parse($row['occurred_at'])->month === $today->month
                && Carbon::parse($row['occurred_at'])->year === $today->year)->count(),
            'critical' => $filtered->where('is_critical', true)->count(),
        ];
    }
}
