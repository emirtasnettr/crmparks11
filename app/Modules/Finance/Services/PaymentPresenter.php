<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Modules\Finance\Data\PaymentFormData;
use App\Modules\Finance\Models\FinancePayment;
use App\Modules\Finance\Models\FinancePaymentLine;

class PaymentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(FinancePayment $payment): array
    {
        return $this->enrich($payment);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(FinancePayment $payment): array
    {
        return $this->enrich($payment, detailed: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(FinancePayment $payment, bool $detailed = false): array
    {
        $payment->loadMissing(['courier', 'agency', 'earningLine', 'currentAccount', 'lines']);

        $totalAmount = (float) $payment->total_amount;
        $paidAmount = (float) $payment->paid_amount;
        $remaining = round($totalAmount - $paidAmount, 2);
        $latestLine = $payment->lines->sortByDesc('payment_date')->first();

        $row = [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'recipient_type' => $payment->recipient_type,
            'recipient_id' => $payment->recipient_id,
            'recipient_name' => $payment->recipient_name ?? '—',
            'earning_id' => $payment->earning_line_id,
            'earning_reference' => $this->earningReference($payment),
            'payment_date' => $latestLine?->payment_date?->toDateString(),
            'scheduled_date' => $payment->scheduled_date->toDateString(),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remaining,
            'payment_method' => $latestLine?->payment_method,
            'payment_reference' => $latestLine?->payment_reference,
            'bank_account' => $latestLine?->bank_account,
            'status' => $payment->status,
            'is_active' => $payment->is_active,
            'source' => $payment->source,
            'current_account_id' => $payment->current_account_id,
            'current_account_code' => $payment->currentAccount?->code,
            'created_at' => $payment->created_at?->toDateString(),
            'description' => $payment->description,
            'notes' => $payment->notes,
            'recipient_type_label' => PaymentFormData::recipientTypes()[$payment->recipient_type] ?? $payment->recipient_type,
            'status_label' => PaymentFormData::paymentStatuses()[$payment->status] ?? $payment->status,
            'payment_method_label' => $latestLine?->payment_method
                ? (PaymentFormData::paymentMethods()[$latestLine->payment_method] ?? $latestLine->payment_method)
                : '—',
            'source_label' => PaymentFormData::sources()[$payment->source] ?? $payment->source,
            'total_amount_formatted' => MoneyCalculator::format($totalAmount),
            'paid_amount_formatted' => MoneyCalculator::format($paidAmount),
            'remaining_amount_formatted' => MoneyCalculator::format($remaining),
            'payment_date_formatted' => $latestLine?->payment_date?->format('d.m.Y') ?? '—',
            'scheduled_date_formatted' => $payment->scheduled_date->format('d.m.Y'),
            'created_at_formatted' => $payment->created_at?->format('d.m.Y') ?? '—',
            'earning_reference_display' => $this->earningReference($payment) ?? '—',
            'recipient_filter_key' => $payment->recipient_type.':'.$payment->recipient_id,
        ];

        if (! $detailed) {
            return $row;
        }

        $row['recipient_info'] = $this->buildRecipientInfo($payment);
        $row['earning_info'] = $payment->earning_line_id ? [
            'id' => $payment->earning_line_id,
            'reference' => $this->earningReference($payment),
            'amount_formatted' => $row['total_amount_formatted'],
            'type_label' => $row['recipient_type_label'].' Hakedişi',
        ] : null;
        $row['payment_info'] = [
            'method' => $row['payment_method_label'],
            'reference' => $latestLine?->payment_reference ?? '—',
            'bank_account' => $latestLine?->bank_account ?? '—',
            'date' => $row['payment_date_formatted'],
            'status' => $row['status_label'],
        ];
        $row['payment_history'] = $this->buildPaymentHistory($payment);
        $row['current_account_movement'] = $paidAmount > 0 ? [
            'code' => $payment->currentAccount?->code,
            'document_no' => $latestLine?->payment_reference ?? $payment->reference,
            'date' => $row['payment_date_formatted'],
            'type_label' => 'Ödeme',
            'debit' => $paidAmount,
            'credit' => 0,
            'debit_formatted' => MoneyCalculator::format($paidAmount),
            'credit_formatted' => '—',
            'description' => 'Ödeme: '.$payment->reference,
        ] : null;
        $row['receipts'] = $this->buildReceipts($payment, $row);

        return $row;
    }

    /**
     * @return array<string, string>
     */
    private function buildRecipientInfo(FinancePayment $payment): array
    {
        return [
            'type' => PaymentFormData::recipientTypes()[$payment->recipient_type] ?? '—',
            'name' => $payment->recipient_name ?? '—',
            'code' => $payment->currentAccount?->code ?? '—',
            'phone' => $payment->courier?->phone ?? $payment->agency?->phone ?? '—',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPaymentHistory(FinancePayment $payment): array
    {
        return $payment->lines
            ->sortBy('payment_date')
            ->values()
            ->map(function (FinancePaymentLine $line, int $index) use ($payment): array {
                return [
                    'id' => $line->id,
                    'date' => $line->payment_date->format('d.m.Y'),
                    'amount' => (float) $line->amount,
                    'amount_formatted' => MoneyCalculator::format((float) $line->amount),
                    'method' => $line->payment_method
                        ? (PaymentFormData::paymentMethods()[$line->payment_method] ?? $line->payment_method)
                        : '—',
                    'reference' => $line->payment_reference ?? ($payment->reference.'-'.($index + 1)),
                    'bank' => $line->bank_account ? explode(' — ', $line->bank_account)[0] : '—',
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array<string, string>>
     */
    private function buildReceipts(FinancePayment $payment, array $row): array
    {
        if ($payment->paid_amount <= 0) {
            return [];
        }

        $items = [
            [
                'name' => 'Dekont-'.$payment->reference.'.pdf',
                'type' => 'Banka Dekontu',
                'date' => $row['payment_date_formatted'],
            ],
        ];

        if ($payment->status === 'partial' && $payment->lines->count() > 1) {
            $items[] = [
                'name' => 'Dekont-'.$payment->reference.'-2.pdf',
                'type' => 'Kısmi Ödeme',
                'date' => $row['payment_date_formatted'],
            ];
        }

        return $items;
    }

    private function earningReference(FinancePayment $payment): ?string
    {
        if ($payment->earning_line_id === null) {
            return null;
        }

        $prefix = match ($payment->recipient_type) {
            'courier' => 'HKD',
            'agency' => 'AHK',
            default => 'HKM',
        };

        return sprintf('%s-%d-%04d', $prefix, $payment->scheduled_date->year, $payment->earning_line_id);
    }
}
