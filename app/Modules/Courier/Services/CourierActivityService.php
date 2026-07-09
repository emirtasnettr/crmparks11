<?php

namespace App\Modules\Courier\Services;

use App\Models\Document;
use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Courier\Data\CourierActivityFormData;
use App\Modules\Courier\Models\Courier;
use App\Modules\Courier\Models\CourierBankAccount;
use App\Modules\Courier\Models\CourierVehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourierActivityService
{
    /**
     * @var array<int, class-string>
     */
    private const SUBJECT_TYPES = [
        Courier::class,
        CourierVehicle::class,
        CourierBankAccount::class,
        Document::class,
    ];

    public function __construct(
        private readonly CourierActivityPresenter $presenter,
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

        return $this->applyCourierFilter($logs, $filters);
    }

    /**
     * @return Collection<int, ActivityLog>
     */
    public function forCourier(int $courierId): Collection
    {
        return $this->filter(['courier_id' => $courierId]);
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
     * @return array<int, array{id: int, name: string}>
     */
    public function couriers(): array
    {
        return Courier::query()
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn (Courier $courier) => [
                'id' => $courier->id,
                'name' => $courier->full_name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function users(): array
    {
        return User::query()
            ->whereIn('id', $this->courierActivityQuery()->distinct()->pluck('user_id'))
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

        return $this->courierActivityQuery()
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

    private function courierActivityQuery(): Builder
    {
        $courierDocumentIds = Document::query()
            ->where('documentable_type', Courier::class)
            ->pluck('id');

        return ActivityLog::query()
            ->whereIn('action', array_keys(CourierActivityFormData::actionTypes()))
            ->where(function (Builder $query) use ($courierDocumentIds): void {
                $query->whereIn('subject_type', [
                    Courier::class,
                    CourierVehicle::class,
                    CourierBankAccount::class,
                ]);

                if ($courierDocumentIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $inner) use ($courierDocumentIds): void {
                        $inner->where('subject_type', Document::class)
                            ->whereIn('subject_id', $courierDocumentIds);
                    });
                }
            });
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ActivityLog>
     */
    private function applyCourierFilter(Collection $logs, array $filters): Collection
    {
        if (empty($filters['courier_id']) || $filters['courier_id'] === 'all') {
            return $logs;
        }

        $courierId = (int) $filters['courier_id'];

        return $logs
            ->filter(fn (ActivityLog $log) => $this->presenter->resolveCourierId($log) === $courierId)
            ->values();
    }
}
