<?php

namespace App\Modules\Courier\Services;

use App\Core\Helpers\MoneyCalculator;
use App\Models\EarningLine;
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
        $netPayment = round($earningAmount, 2);

        return [
            'id' => $line->id,
            'courier_id' => $line->courier_id,
            'business_id' => $line->business_id,
            'agency_id' => $line->courier?->agency_id,
            'courier_name' => $line->courier?->full_name ?? '—',
            'business_name' => $line->business?->company_name ?? '—',
            'agency_name' => $line->courier?->agency?->company_name ?? '—',
            'courier_type' => $line->courier?->courier_type ?? 'independent',
            'period_month' => $line->period_month,
            'period_year' => $line->period_year,
            'period_label' => ($months[$line->period_month] ?? '').' '.$line->period_year,
            'package_count' => (int) $line->package_count,
            'unit_price' => (float) $line->courier_unit_price,
            'earning_amount' => $earningAmount,
            'extra_payment' => (float) $line->extra_payment,
            'deduction' => $deduction,
            'net_payment' => $netPayment,
            'paid_amount' => $paymentStatus === 'paid' ? $netPayment : 0,
            'payment_status' => $paymentStatus,
            'payment_date' => $line->paid_at?->toDateString(),
            'payment_date_formatted' => $line->paid_at?->format('d.m.Y') ?? '—',
            'status' => $statusCode,
            'description' => $line->description,
            'earning_amount_formatted' => MoneyCalculator::format($earningAmount),
            'net_payment_formatted' => MoneyCalculator::format($netPayment),
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
