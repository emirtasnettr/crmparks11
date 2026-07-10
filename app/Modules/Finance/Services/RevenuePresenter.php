<?php

namespace App\Modules\Finance\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Finance\Data\RevenueFormData;
use App\Modules\Finance\Models\FinanceRevenue;
use Carbon\Carbon;

class RevenuePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(FinanceRevenue $revenue): array
    {
        return $this->enrich($revenue);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(FinanceRevenue $revenue): array
    {
        return $this->enrich($revenue, detailed: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(FinanceRevenue $revenue, bool $detailed = false): array
    {
        $revenue->loadMissing(['business.city', 'business.district', 'earningLine', 'currentAccount']);

        $business = $revenue->business;
        $amount = (float) $revenue->amount;
        $vatAmount = round($amount * ($revenue->vat_rate / 100), 2);
        $grossAmount = round($amount + $vatAmount, 2);
        $periodLabel = $revenue->period_label ?: $this->formatPeriodLabel($revenue);

        $row = [
            'id' => $revenue->id,
            'reference' => $revenue->reference,
            'business_id' => $revenue->business_id,
            'revenue_type' => $revenue->revenue_type,
            'period_month' => $revenue->period_month,
            'period_year' => $revenue->period_year,
            'period_label' => $periodLabel,
            'invoice_no' => $revenue->invoice_no,
            'invoice_status' => $revenue->invoice_status,
            'amount' => $amount,
            'vat_rate' => (int) $revenue->vat_rate,
            'collection_status' => $revenue->collection_status,
            'collection_date' => $revenue->collection_date?->toDateString(),
            'revenue_date' => $revenue->revenue_date->toDateString(),
            'created_at' => $revenue->created_at?->toDateString(),
            'description' => $revenue->description,
            'earning_id' => $revenue->earning_line_id,
            'earning_reference' => $revenue->earning_line_id
                ? sprintf('IHK-%d-%04d', $revenue->period_year ?? $revenue->revenue_date->year, $revenue->earning_line_id)
                : null,
            'current_account_id' => $revenue->current_account_id,
            'current_account_code' => $revenue->currentAccount?->code,
            'notes' => $revenue->notes,
            'can_update' => true,
            'business_name' => $business?->company_name ?? '—',
            'business_brand' => $business?->brand_name ?? '—',
            'business_phone' => $business?->phone ?? '—',
            'business_city' => $business?->city?->name ?? '—',
            'business_district' => $business?->district?->name ?? '—',
            'revenue_type_label' => RevenueFormData::revenueTypes()[$revenue->revenue_type] ?? $revenue->revenue_type,
            'collection_status_label' => RevenueFormData::collectionStatuses()[$revenue->collection_status] ?? $revenue->collection_status,
            'invoice_status_label' => RevenueFormData::invoiceStatuses()[$revenue->invoice_status] ?? $revenue->invoice_status,
            'amount_formatted' => money_excl_vat($amount),
            'vat_amount' => $vatAmount,
            'vat_amount_formatted' => MoneyCalculator::formatVatAmount($vatAmount),
            'gross_amount' => $grossAmount,
            'gross_amount_formatted' => MoneyCalculator::formatIncludingVat($grossAmount),
            'revenue_date_formatted' => $revenue->revenue_date->format('d.m.Y'),
            'created_at_formatted' => $revenue->created_at?->format('d.m.Y') ?? '—',
            'collection_date_formatted' => $revenue->collection_date?->format('d.m.Y') ?? '—',
            'period_display' => $periodLabel ?: '—',
            'invoice_no_display' => $revenue->invoice_no ?? '—',
        ];

        if (! $detailed) {
            return $row;
        }

        $row['current_account_movement'] = [
            'document_no' => $revenue->invoice_no ?? $revenue->reference,
            'date' => $row['revenue_date_formatted'],
            'type_label' => $revenue->collection_status === 'collected' ? 'Tahsilat' : 'Fatura',
            'debit' => $revenue->collection_status === 'collected' ? 0 : $amount,
            'credit' => $revenue->collection_status === 'collected' ? $amount : 0,
            'debit_formatted' => $revenue->collection_status === 'collected' ? '—' : money_excl_vat($amount),
            'credit_formatted' => $revenue->collection_status === 'collected' ? money_excl_vat($amount) : '—',
            'description' => 'Gelir kaydı: '.$revenue->reference,
        ];

        $row['collection_info'] = [
            'status' => $revenue->collection_status,
            'status_label' => $row['collection_status_label'],
            'date' => $row['collection_date_formatted'],
            'method' => $revenue->collection_status === 'collected' ? 'Banka Havalesi' : '—',
            'reference' => $revenue->collection_status === 'collected'
                ? sprintf('THS-%d-%04d', $revenue->revenue_date->year, $revenue->id)
                : '—',
        ];

        $row['invoice_info'] = [
            'status' => $revenue->invoice_status,
            'status_label' => $row['invoice_status_label'],
            'invoice_no' => $row['invoice_no_display'],
            'issue_date' => $revenue->invoice_no ? $row['revenue_date_formatted'] : '—',
            'due_date' => $revenue->invoice_no
                ? $revenue->revenue_date->copy()->addDays(15)->format('d.m.Y')
                : '—',
        ];

        $row['earning_info'] = $revenue->earning_line_id ? [
            'id' => $revenue->earning_line_id,
            'reference' => $row['earning_reference'],
            'period' => $periodLabel,
            'amount_formatted' => $row['amount_formatted'],
        ] : null;

        return $row;
    }

    private function formatPeriodLabel(FinanceRevenue $revenue): ?string
    {
        if ($revenue->period_month === null || $revenue->period_year === null) {
            return null;
        }

        $months = BusinessEarningFormData::months();

        return ($months[$revenue->period_month] ?? '').' '.$revenue->period_year;
    }
}
