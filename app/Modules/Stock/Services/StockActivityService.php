<?php

namespace App\Modules\Stock\Services;

use App\Models\User;
use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Stock\Data\StockActivityFormData;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockActivityService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ActivityLog>
     */
    public function filter(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with(['user', 'subject'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function users(): array
    {
        return User::query()
            ->whereIn('id', $this->stockActivityQuery()->distinct()->pluck('user_id')->filter())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function products(): array
    {
        return StockProduct::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (StockProduct $product) => [
                'id' => $product->id,
                'label' => $product->sku
                    ? "{$product->name} ({$product->sku})"
                    : $product->name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $today = Carbon::today();

        return $this->stockActivityQuery()
            ->when(! empty($filters['user_id']) && $filters['user_id'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('user_id', (int) $filters['user_id']);
            })
            ->when(! empty($filters['action']) && $filters['action'] !== 'all', function (Builder $query) use ($filters): void {
                $query->where('action', $filters['action']);
            })
            ->when(! empty($filters['product_id']) && $filters['product_id'] !== 'all', function (Builder $query) use ($filters): void {
                $productId = (int) $filters['product_id'];
                $query->where(function (Builder $inner) use ($productId): void {
                    $inner->where(function (Builder $productSubject) use ($productId): void {
                        $productSubject
                            ->where('subject_type', (new StockProduct)->getMorphClass())
                            ->where('subject_id', $productId);
                    })->orWhere(function (Builder $assignmentSubject) use ($productId): void {
                        $assignmentIds = StockAssignment::query()
                            ->where('stock_product_id', $productId)
                            ->pluck('id');

                        $assignmentSubject
                            ->where('subject_type', (new StockAssignment)->getMorphClass())
                            ->whereIn('subject_id', $assignmentIds);
                    })->orWhere('new_values->product_id', $productId)
                        ->orWhere('old_values->product_id', $productId);
                });
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
            })
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%");
                });
            });
    }

    private function stockActivityQuery(): Builder
    {
        return ActivityLog::query()->whereIn('action', StockActivityFormData::actionKeys());
    }
}
