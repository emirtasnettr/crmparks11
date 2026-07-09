<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Finance\Data\InvoiceFormData;
use App\Modules\Finance\Models\FinanceInvoice;
use Carbon\Carbon;

class InvoicePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(FinanceInvoice $invoice): array
    {
        return $this->enrich($invoice);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(FinanceInvoice $invoice): array
    {
        return $this->enrich($invoice, detailed: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(FinanceInvoice $invoice, bool $detailed = false): array
    {
        $invoice->loadMissing(['business.city', 'business.district', 'earningLine', 'currentAccount', 'collection']);

        $business = $invoice->business;
        $subtotal = (float) $invoice->subtotal;
        $collectedAmount = (float) $invoice->collected_amount;
        $remaining = round($subtotal - $collectedAmount, 2);
        $periodLabel = $this->earningPeriodLabel($invoice);

        $row = [
            'id' => $invoice->id,
            'reference' => $invoice->reference,
            'business_id' => $invoice->business_id,
            'earning_id' => $invoice->earning_line_id,
            'earning_reference' => $invoice->earning_line_id
                ? sprintf('ISH-%06d', $invoice->earning_line_id)
                : null,
            'earning_period' => $periodLabel,
            'invoice_type' => $invoice->invoice_type,
            'invoice_status' => $invoice->invoice_status,
            'collection_status' => $invoice->collection_status,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'subtotal' => $subtotal,
            'vat_rate' => (int) $invoice->vat_rate,
            'vat_amount' => (float) $invoice->vat_amount,
            'grand_total' => (float) $invoice->grand_total,
            'collected_amount' => $collectedAmount,
            'collection_id' => $invoice->collection_id,
            'source' => $invoice->source,
            'current_account_id' => $invoice->current_account_id,
            'current_account_code' => $invoice->currentAccount?->code,
            'e_invoice_uuid' => $invoice->e_invoice_uuid,
            'e_archive_uuid' => $invoice->e_archive_uuid,
            'gib_status' => $invoice->gib_status,
            'pdf_filename' => $invoice->pdf_filename,
            'description' => $invoice->description,
            'notes' => $invoice->notes,
            'created_at' => $invoice->created_at?->toDateString(),
            'business_name' => $business?->company_name ?? '—',
            'business_brand' => $business?->brand_name ?? '—',
            'business_tax_no' => $business?->tax_number ?? '—',
            'business_address' => trim(($business?->address ?? '').', '.($business?->city?->name ?? '')),
            'invoice_type_label' => InvoiceFormData::invoiceTypes()[$invoice->invoice_type] ?? $invoice->invoice_type,
            'invoice_status_label' => InvoiceFormData::invoiceStatuses()[$invoice->invoice_status] ?? $invoice->invoice_status,
            'collection_status_label' => InvoiceFormData::collectionStatuses()[$invoice->collection_status] ?? $invoice->collection_status,
            'source_label' => InvoiceFormData::sources()[$invoice->source] ?? $invoice->source,
            'subtotal_formatted' => MoneyCalculator::format($subtotal),
            'vat_amount_formatted' => MoneyCalculator::formatVatAmount((float) $invoice->vat_amount),
            'grand_total_formatted' => MoneyCalculator::formatIncludingVat((float) $invoice->grand_total),
            'collected_amount_formatted' => MoneyCalculator::format($collectedAmount),
            'remaining_amount' => $remaining,
            'remaining_amount_formatted' => MoneyCalculator::format($remaining),
            'invoice_date_formatted' => $invoice->invoice_date->format('d.m.Y'),
            'due_date_formatted' => $invoice->due_date->format('d.m.Y'),
            'created_at_formatted' => $invoice->created_at?->format('d.m.Y') ?? '—',
            'earning_period_display' => $periodLabel ?? '—',
            'earning_reference_display' => $invoice->earning_line_id
                ? sprintf('ISH-%06d', $invoice->earning_line_id)
                : '—',
        ];

        if (! $detailed) {
            return $row;
        }

        $row['earning_info'] = $invoice->earning_line_id ? [
            'id' => $invoice->earning_line_id,
            'reference' => sprintf('ISH-%06d', $invoice->earning_line_id),
            'period' => $periodLabel,
            'amount_formatted' => $row['subtotal_formatted'],
        ] : null;

        $row['collection_info'] = $invoice->collection_id ? [
            'id' => $invoice->collection_id,
            'reference' => $invoice->collection?->reference ?? sprintf('TAH-%d-%06d', $invoice->invoice_date->year, $invoice->collection_id),
            'status' => $row['collection_status_label'],
            'collected_formatted' => $row['collected_amount_formatted'],
            'remaining_formatted' => $row['remaining_amount_formatted'],
        ] : null;

        $row['current_account_movements'] = $invoice->invoice_status === 'issued' ? [
            [
                'code' => $invoice->currentAccount?->code,
                'document_no' => $invoice->reference,
                'date' => $row['invoice_date_formatted'],
                'type_label' => 'Gelir Faturası',
                'debit' => $subtotal,
                'credit' => 0,
                'debit_formatted' => MoneyCalculator::format($subtotal),
                'credit_formatted' => '—',
                'description' => 'Fatura: '.$invoice->reference,
            ],
        ] : [];

        if ($collectedAmount > 0 && $invoice->collection_id) {
            $row['current_account_movements'][] = [
                'code' => $invoice->currentAccount?->code,
                'document_no' => $invoice->collection?->reference ?? sprintf('TAH-%d-%06d', $invoice->invoice_date->year, $invoice->collection_id),
                'date' => $row['due_date_formatted'],
                'type_label' => 'Tahsilat',
                'debit' => 0,
                'credit' => $collectedAmount,
                'debit_formatted' => '—',
                'credit_formatted' => MoneyCalculator::format($collectedAmount),
                'description' => 'Tahsilat: '.($invoice->collection?->reference ?? $invoice->reference),
            ];
        }

        $row['integration_info'] = [
            'type' => $row['invoice_type_label'],
            'uuid' => $invoice->e_invoice_uuid ?? $invoice->e_archive_uuid ?? '—',
            'gib_status' => match ($invoice->gib_status) {
                'sent' => 'GİB\'e Gönderildi',
                'draft' => 'Taslak',
                'cancelled' => 'İptal Edildi',
                default => 'Uygulanmaz',
            },
        ];

        return $row;
    }

    private function earningPeriodLabel(FinanceInvoice $invoice): ?string
    {
        $line = $invoice->earningLine;

        if ($line === null) {
            return null;
        }

        $months = BusinessEarningFormData::months();

        return ($months[$line->period_month] ?? '').' '.$line->period_year;
    }
}
