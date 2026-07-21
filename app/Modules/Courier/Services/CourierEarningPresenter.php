<?php

namespace App\Modules\Courier\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
use App\Modules\Business\Data\BusinessCommercialContractFormData;
use App\Modules\Courier\Data\CourierEarningFormData;
use App\Support\EarningStatusMapper;

class CourierEarningPresenter
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

        $months = CourierEarningFormData::months();
        $statusCode = EarningStatusMapper::toUiCode($line->status?->code ?? 'draft');
        $paymentStatus = $this->paymentStatus($line, $statusCode);
        $earningAmount = (float) $line->net_courier_payment;
        $deduction = (float) $line->deduction;
        $extraPayment = (float) $line->extra_payment;
        $netPayment = round($earningAmount, 2);
        $paidAmount = $paymentStatus === 'paid' ? $netPayment : 0.0;

        $extraPayments = [];
        if ($extraPayment > 0) {
            $extraPayments[] = [
                'label' => 'Ek Ödeme',
                'amount' => $extraPayment,
            ];
        }

        $deductions = [];
        if ($deduction > 0) {
            $deductions[] = [
                'label' => 'Kesinti',
                'amount' => $deduction,
            ];
        }

        return [
            'id' => $line->id,
            'courier_id' => $line->courier_id,
            'business_id' => $line->business_id,
            'agency_id' => $line->courier?->agency_id,
            'courier_name' => $line->courier?->full_name ?? '—',
            'courier_phone' => $line->courier?->phone ?? '—',
            'business_name' => $line->business?->company_name ?? $line->business?->displayName() ?? '—',
            'business_brand' => $line->business?->brand_name ?? '—',
            'agency_name' => $line->courier?->agency?->displayName() ?? '—',
            'courier_type' => $line->courier?->courier_type ?? 'independent',
            'period_month' => $line->period_month,
            'period_year' => $line->period_year,
            'work_date' => $line->work_date?->toDateString(),
            'period_label' => $line->work_date
                ? $line->work_date->format('d.m.Y')
                : trim(($months[$line->period_month] ?? '').' '.$line->period_year),
            'pricing_model' => $line->pricing_model,
            'pricing_model_label' => BusinessCommercialContractFormData::workTypes()[$line->pricing_model]
                ?? ($line->pricing_model ?: '—'),
            'package_count' => (int) $line->package_count,
            'worked_hours' => $line->resolvedWorkedHours(),
            'unit_price' => (float) $line->courier_unit_price,
            'earning_amount' => $earningAmount,
            'extra_payment' => $extraPayment,
            'extra_payments' => $extraPayments,
            'deduction' => $deduction,
            'deductions' => $deductions,
            'net_payment' => $netPayment,
            'paid_amount' => $paidAmount,
            'remaining_payment' => max(0, round($netPayment - $paidAmount, 2)),
            'payment_status' => $paymentStatus,
            'payment_date' => $line->paid_at?->toDateString(),
            'payment_date_formatted' => $line->paid_at?->format('d.m.Y') ?? '—',
            'status' => $statusCode,
            'description' => $line->description,
            'earning_amount_formatted' => MoneyCalculator::format($earningAmount),
            'net_payment_formatted' => MoneyCalculator::format($netPayment),
            'can_approve' => in_array($statusCode, ['draft', 'pending'], true),
            'can_delete' => ! in_array($statusCode, ['paid', 'cancelled'], true),
        ];
    }

    private function paymentStatus(EarningLine $line, string $statusCode): string
    {
        if ($statusCode === 'cancelled') {
            return 'cancelled';
        }

        if ($line->paid_at !== null) {
            return 'paid';
        }

        return 'pending';
    }
}
