<?php

namespace App\Modules\Business\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Support\EarningStatusMapper;

class BusinessEarningPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function indexRow(EarningLine $line): array
    {
        return $this->enrich($line);
    }

    /**
     * @return array<string, mixed>
     */
    public function showRow(EarningLine $line): array
    {
        return $this->enrich($line);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrich(EarningLine $line): array
    {
        $line->loadMissing(['business', 'courier.agency', 'status']);

        $months = BusinessEarningFormData::months();
        $statusCode = EarningStatusMapper::toUiCode($line->status?->code ?? 'draft');
        $courierPayment = (float) $line->courier_total;
        $extraExpense = (float) $line->extra_expense;
        $revenue = (float) $line->revenue_total;
        $profit = (float) $line->profit;
        $pricingModel = $line->pricing_model ?: 'per_package';

        return [
            'id' => $line->id,
            'business_id' => $line->business_id,
            'courier_id' => $line->courier_id,
            'agency_id' => $line->courier?->agency_id,
            'business_name' => $line->business?->company_name ?? '—',
            'courier_name' => $line->courier?->full_name ?? '—',
            'agency_name' => $line->courier?->agency?->company_name ?? '—',
            'period_month' => $line->period_month,
            'period_year' => $line->period_year,
            'period_label' => ($months[$line->period_month] ?? '').' '.$line->period_year,
            'pricing_model' => $pricingModel,
            'pricing_model_label' => BusinessEarningFormData::pricingModels()[$pricingModel] ?? $pricingModel,
            'package_count' => (int) $line->package_count,
            'revenue_unit_price' => (float) $line->revenue_unit_price,
            'courier_unit_price' => (float) $line->courier_unit_price,
            'extra_income' => (float) $line->extra_payment,
            'extra_expense' => $extraExpense,
            'deduction' => (float) $line->deduction,
            'status' => $statusCode,
            'status_label' => BusinessEarningFormData::statuses()[$statusCode] ?? $statusCode,
            'description' => $line->description,
            'revenue' => $revenue,
            'courier_payment' => $courierPayment,
            'total_expense' => round($courierPayment + $extraExpense, 2),
            'profit' => $profit,
            'revenue_formatted' => MoneyCalculator::format($revenue),
            'courier_payment_formatted' => MoneyCalculator::format($courierPayment),
            'total_expense_formatted' => MoneyCalculator::format($courierPayment + $extraExpense),
            'profit_formatted' => MoneyCalculator::format($profit),
        ];
    }
}
