<?php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Data\StockFormData;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;

class StockInventoryService
{
    public function __construct(
        private readonly StockProductPresenter $presenter,
    ) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     products: array<int, array<string, mixed>>,
     *     critical_products: array<int, array<string, mixed>>,
     *     recent_assignments: array<int, array<string, mixed>>,
     *     threshold: int
     * }
     */
    public function dashboard(): array
    {
        $products = StockProduct::query()
            ->where('status', 'active')
            ->withSum([
                'assignments as assigned_quantity' => fn ($q) => $q->where('status', 'assigned'),
            ], 'quantity')
            ->orderBy('name')
            ->get();

        $productRows = $products
            ->map(fn (StockProduct $product) => $this->productRow($product))
            ->values()
            ->all();

        $critical = collect($productRows)
            ->filter(fn (array $row) => in_array($row['stock_level'], ['critical', 'out'], true))
            ->sortBy('quantity')
            ->values()
            ->all();

        $recentAssignments = StockAssignment::query()
            ->with(['product', 'courier', 'assigner'])
            ->where('status', 'assigned')
            ->latest('assigned_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (StockAssignment $assignment) => $this->presenter->assignmentRow($assignment))
            ->all();

        return [
            'summary' => [
                'total_products' => $products->count(),
                'total_quantity' => (int) $products->sum('quantity'),
                'critical_count' => collect($productRows)->where('stock_level', 'critical')->count(),
                'out_of_stock' => collect($productRows)->where('stock_level', 'out')->count(),
                'assigned_items' => (int) StockAssignment::query()->where('status', 'assigned')->sum('quantity'),
            ],
            'products' => $productRows,
            'critical_products' => $critical,
            'recent_assignments' => $recentAssignments,
            'threshold' => StockFormData::CRITICAL_STOCK_THRESHOLD,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productRow(StockProduct $product): array
    {
        $row = $this->presenter->indexRow($product);
        $level = $this->resolveStockLevel((int) $product->quantity);

        return array_merge($row, [
            'stock_level' => $level,
            'stock_level_label' => StockFormData::stockLevelLabels()[$level] ?? $level,
        ]);
    }

    private function resolveStockLevel(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out';
        }

        if ($quantity <= StockFormData::CRITICAL_STOCK_THRESHOLD) {
            return 'critical';
        }

        return 'ok';
    }
}
