<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Modules\Finance\Data\CollectionFormData;
use App\Modules\Finance\Models\FinanceCollection;
use App\Modules\Finance\Models\FinanceCollectionPayment;
use Carbon\Carbon;

class CollectionPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(FinanceCollection $collection): array
    {
        return $this->enrich($collection);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(FinanceCollection $collection): array
    {
        return $this->enrich($collection, detailed: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(FinanceCollection $collection, bool $detailed = false): array
    {
        $collection->loadMissing(['business.city', 'revenue', 'currentAccount', 'payments']);

        $business = $collection->business;
        $totalAmount = (float) $collection->total_amount;
        $collectedAmount = (float) $collection->collected_amount;
        $remaining = round($totalAmount - $collectedAmount, 2);
        $latestPayment = $collection->payments->sortByDesc('payment_date')->first();

        $row = [
            'id' => $collection->id,
            'reference' => $collection->reference,
            'business_id' => $collection->business_id,
            'revenue_id' => $collection->revenue_id,
            'revenue_reference' => $collection->revenue?->reference,
            'invoice_no' => $collection->invoice_no,
            'due_date' => $collection->due_date->toDateString(),
            'collection_date' => $latestPayment?->payment_date?->toDateString(),
            'total_amount' => $totalAmount,
            'collected_amount' => $collectedAmount,
            'remaining_amount' => $remaining,
            'payment_method' => $latestPayment?->payment_method,
            'payment_reference' => $latestPayment?->payment_reference,
            'bank' => $latestPayment?->bank,
            'status' => $collection->status,
            'source' => $collection->source,
            'current_account_id' => $collection->current_account_id,
            'current_account_code' => $collection->currentAccount?->code,
            'created_at' => $collection->created_at?->toDateString(),
            'description' => $collection->description,
            'notes' => $collection->notes,
            'can_update' => $collection->status !== 'collected',
            'business_name' => $business?->company_name ?? '—',
            'business_brand' => $business?->brand_name ?? '—',
            'business_phone' => $business?->phone ?? '—',
            'business_city' => $business?->city?->name ?? '—',
            'status_label' => CollectionFormData::collectionStatuses()[$collection->status] ?? $collection->status,
            'payment_method_label' => $latestPayment?->payment_method
                ? (CollectionFormData::paymentMethods()[$latestPayment->payment_method] ?? $latestPayment->payment_method)
                : '—',
            'total_amount_formatted' => MoneyCalculator::format($totalAmount),
            'collected_amount_formatted' => MoneyCalculator::format($collectedAmount),
            'remaining_amount_formatted' => MoneyCalculator::format($remaining),
            'due_date_formatted' => $collection->due_date->format('d.m.Y'),
            'collection_date_formatted' => $latestPayment?->payment_date?->format('d.m.Y') ?? '—',
            'created_at_formatted' => $collection->created_at?->format('d.m.Y') ?? '—',
            'revenue_reference_display' => $collection->revenue?->reference ?? '—',
            'invoice_no_display' => $collection->invoice_no ?? '—',
        ];

        if (! $detailed) {
            return $row;
        }

        $row['collection_history'] = $this->buildCollectionHistory($collection);
        $row['receipts'] = $this->buildReceipts($collection, $row);
        $row['revenue_info'] = $collection->revenue_id ? [
            'id' => $collection->revenue_id,
            'reference' => $collection->revenue?->reference,
            'invoice_no' => $collection->invoice_no,
            'amount_formatted' => $row['total_amount_formatted'],
        ] : null;
        $row['invoice_info'] = [
            'invoice_no' => $collection->invoice_no,
            'due_date' => $row['due_date_formatted'],
            'total_formatted' => $row['total_amount_formatted'],
        ];
        $row['current_account_movement'] = $collectedAmount > 0 ? [
            'code' => $collection->currentAccount?->code,
            'document_no' => $latestPayment?->payment_reference ?? $collection->reference,
            'date' => $row['collection_date_formatted'],
            'type_label' => 'Tahsilat',
            'debit' => 0,
            'credit' => $collectedAmount,
            'debit_formatted' => '—',
            'credit_formatted' => MoneyCalculator::format($collectedAmount),
            'description' => 'Tahsilat: '.$collection->reference,
        ] : null;

        return $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCollectionHistory(FinanceCollection $collection): array
    {
        return $collection->payments
            ->sortBy('payment_date')
            ->values()
            ->map(function (FinanceCollectionPayment $payment, int $index) use ($collection): array {
                return [
                    'id' => $payment->id,
                    'date' => $payment->payment_date->format('d.m.Y'),
                    'amount' => (float) $payment->amount,
                    'amount_formatted' => MoneyCalculator::format((float) $payment->amount),
                    'method' => $payment->payment_method
                        ? (CollectionFormData::paymentMethods()[$payment->payment_method] ?? $payment->payment_method)
                        : '—',
                    'reference' => $payment->payment_reference ?? ($collection->reference.'-'.($index + 1)),
                    'bank' => $payment->bank ?? '—',
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, string>>
     */
    private function buildReceipts(FinanceCollection $collection, array $row): array
    {
        if ($collection->receipt_path) {
            return [
                [
                    'name' => $collection->receipt_original_name
                        ?: ('Dekont-'.$collection->reference.'.pdf'),
                    'type' => 'Banka Dekontu',
                    'date' => $collection->receipt_uploaded_at?->format('d.m.Y')
                        ?? $row['collection_date_formatted'],
                    'download_url' => route('finance.collections.receipts.download', $collection->id),
                ],
            ];
        }

        return [];
    }
}
