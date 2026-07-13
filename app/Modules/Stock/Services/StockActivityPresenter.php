<?php

namespace App\Modules\Stock\Services;

use App\Modules\ActivityLog\Models\ActivityLog;
use App\Modules\Stock\Data\StockActivityFormData;
use App\Modules\Stock\Models\StockAssignment;
use App\Modules\Stock\Models\StockProduct;

class StockActivityPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(ActivityLog $log): array
    {
        $productName = $this->resolveProductName($log);
        $quantityDelta = $this->resolveQuantityDelta($log);

        return [
            'id' => $log->id,
            'occurred_at_formatted' => $log->created_at?->format('d.m.Y H:i') ?? '—',
            'action' => $log->action,
            'action_label' => StockActivityFormData::actionTypes()[$log->action] ?? $log->action,
            'product_name' => $productName,
            'user_name' => $log->user?->name ?? 'Sistem',
            'ip_address' => $log->ip_address ?: '—',
            'description' => $log->description ?: '—',
            'quantity_delta' => $quantityDelta,
            'quantity_delta_label' => $this->formatQuantityDelta($quantityDelta),
            'old_quantity' => $log->old_values['quantity'] ?? null,
            'new_quantity' => $log->new_values['quantity'] ?? null,
        ];
    }

    private function resolveProductName(ActivityLog $log): string
    {
        $log->loadMissing('subject');
        $subject = $log->subject;

        if ($subject instanceof StockProduct) {
            return $subject->name;
        }

        if ($subject instanceof StockAssignment) {
            $subject->loadMissing('product');

            return $subject->product?->name
                ?? (string) ($log->new_values['product_name'] ?? $log->old_values['product_name'] ?? '—');
        }

        return (string) ($log->new_values['product_name'] ?? $log->old_values['product_name'] ?? '—');
    }

    private function resolveQuantityDelta(ActivityLog $log): ?int
    {
        if (isset($log->new_values['quantity_delta'])) {
            return (int) $log->new_values['quantity_delta'];
        }

        $old = $log->old_values['quantity'] ?? null;
        $new = $log->new_values['quantity'] ?? null;

        if ($old === null || $new === null) {
            return null;
        }

        return (int) $new - (int) $old;
    }

    private function formatQuantityDelta(?int $delta): string
    {
        if ($delta === null || $delta === 0) {
            return '—';
        }

        return $delta > 0 ? '+'.number_format($delta) : number_format($delta);
    }
}
