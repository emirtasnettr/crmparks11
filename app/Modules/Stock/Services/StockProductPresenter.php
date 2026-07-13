<?php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Data\StockFormData;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;
use Illuminate\Support\Collection;

class StockProductPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(StockProduct $product): array
    {
        if ($product->relationLoaded('assignments')) {
            $assigned = (int) $product->assignments->where('status', 'assigned')->sum('quantity');
        } elseif (array_key_exists('assigned_quantity', $product->getAttributes())) {
            $assigned = (int) $product->assigned_quantity;
        } else {
            $assigned = $product->assignedQuantity();
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku ?: '—',
            'quantity' => $product->quantity,
            'unit' => $product->unit,
            'unit_label' => StockFormData::units()[$product->unit] ?? $product->unit,
            'assigned_quantity' => $assigned,
            'status' => $product->status,
            'status_label' => StockFormData::statuses()[$product->status] ?? $product->status,
            'is_out_of_stock' => $product->quantity === 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function showPayload(StockProduct $product): array
    {
        $product->load([
            'assignments' => fn ($q) => $q->with(['courier', 'assigner'])->latest('assigned_at'),
        ]);

        $activeAssignments = $product->assignments->where('status', 'assigned')->values();

        return array_merge($this->indexRow($product), [
            'description' => $product->description,
            'notes' => $product->notes,
            'sku_raw' => $product->sku,
            'created_at_formatted' => $product->created_at?->format('d.m.Y H:i') ?? '—',
            'assignments' => $activeAssignments
                ->map(fn (StockAssignment $a) => $this->assignmentRow($a))
                ->all(),
            'assignment_history' => $product->assignments
                ->map(fn (StockAssignment $a) => $this->assignmentRow($a))
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function formPayload(StockProduct $product): array
    {
        return [
            'name' => $product->name,
            'sku' => $product->sku ?? '',
            'description' => $product->description ?? '',
            'quantity' => (string) $product->quantity,
            'unit' => $product->unit,
            'status' => $product->status,
            'notes' => $product->notes ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assignmentRow(StockAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'product_id' => $assignment->stock_product_id,
            'product_name' => $assignment->product?->name ?? '—',
            'courier_id' => $assignment->courier_id,
            'courier_name' => $assignment->courier?->full_name ?? '—',
            'quantity' => $assignment->quantity,
            'assigned_at_formatted' => $assignment->assigned_at?->format('d.m.Y') ?? '—',
            'returned_at_formatted' => $assignment->returned_at?->format('d.m.Y') ?? '—',
            'status' => $assignment->status,
            'status_label' => StockFormData::assignmentStatuses()[$assignment->status] ?? $assignment->status,
            'notes' => $assignment->notes,
            'assigned_by' => $assignment->assigner?->name ?? '—',
            'is_assigned' => $assignment->isAssigned(),
        ];
    }

    /**
     * @param  Collection<int, StockAssignment>  $assignments
     * @return array<int, array<string, mixed>>
     */
    public function assignmentIndexRows(Collection $assignments): array
    {
        return $assignments
            ->map(fn (StockAssignment $a) => $this->assignmentRow($a))
            ->values()
            ->all();
    }
}
