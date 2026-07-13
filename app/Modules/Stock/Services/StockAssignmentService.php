<?php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Models\StockAssignment;
use Illuminate\Support\Collection;

class StockAssignmentService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, StockAssignment>
     */
    public function filter(array $filters): Collection
    {
        $query = StockAssignment::query()
            ->with(['product', 'courier', 'assigner']);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('courier', fn ($c) => $c->where('full_name', 'like', "%{$search}%"));
            });
        }

        $status = $filters['status'] ?? 'assigned';
        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        $courierId = $filters['courier_id'] ?? 'all';
        if ($courierId !== 'all' && $courierId !== '') {
            $query->where('courier_id', (int) $courierId);
        }

        $productId = $filters['product_id'] ?? 'all';
        if ($productId !== 'all' && $productId !== '') {
            $query->where('stock_product_id', (int) $productId);
        }

        return $query->latest('assigned_at')->get();
    }
}
