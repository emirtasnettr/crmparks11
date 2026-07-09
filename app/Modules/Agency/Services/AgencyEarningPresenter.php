<?php

namespace App\Modules\Agency\Services;

use App\Models\EarningLine;
use App\Modules\Agency\Data\AgencyEarningFormData;
use App\Modules\Agency\Models\Agency;
use App\Support\EarningStatusMapper;
use Illuminate\Support\Collection;

class AgencyEarningPresenter
{
    /**
     * @param  Collection<int, EarningLine>  $lines
     * @return array<string, mixed>
     */
    public function aggregateRow(Agency $agency, int $periodMonth, int $periodYear, Collection $lines): array
    {
        $months = AgencyEarningFormData::months();
        $grossAmount = round($lines->sum(fn (EarningLine $line) => (float) $line->agency_payment + (float) $line->extra_payment), 2);
        $deduction = round($lines->sum(fn (EarningLine $line) => (float) $line->deduction), 2);
        $netPayment = round($grossAmount - $deduction, 2);
        $paidLines = $lines->filter(fn (EarningLine $line) => $line->paid_at !== null);
        $statusCode = $this->aggregateStatus($lines);
        $paymentStatus = $paidLines->count() === $lines->count() && $lines->isNotEmpty()
            ? 'paid'
            : ($statusCode === 'cancelled' ? 'cancelled' : 'pending');

        return [
            'id' => (int) $lines->min('id'),
            'agency_id' => $agency->id,
            'agency_name' => $agency->company_name,
            'agency_authorized' => '—',
            'agency_city' => $agency->city?->name ?? '—',
            'agency_phone' => $agency->phone ?? '—',
            'agency_email' => $agency->email ?? '—',
            'reference' => sprintf('AHK-%d-%03d', $periodYear, $agency->id),
            'period_month' => $periodMonth,
            'period_year' => $periodYear,
            'period_label' => ($months[$periodMonth] ?? '').' '.$periodYear,
            'period_type' => 'monthly',
            'period_type_label' => 'Aylık',
            'courier_count' => $lines->pluck('courier_id')->unique()->count(),
            'package_count' => (int) $lines->sum('package_count'),
            'gross_amount' => $grossAmount,
            'extra_payment' => round($lines->sum(fn (EarningLine $line) => (float) $line->extra_payment), 2),
            'deduction' => $deduction,
            'net_payment' => $netPayment,
            'paid_amount' => round($paidLines->sum(fn (EarningLine $line) => (float) $line->agency_payment + (float) $line->extra_payment - (float) $line->deduction), 2),
            'payment_status' => $paymentStatus,
            'payment_date' => $paidLines->max('paid_at')?->toDateString(),
            'payment_date_formatted' => $paidLines->max('paid_at')?->format('d.m.Y') ?? '—',
            'status' => $statusCode,
            'description' => null,
            'lines' => $lines->map(fn (EarningLine $line) => [
                'courier_name' => $line->courier?->full_name ?? '—',
                'business_name' => $line->business?->company_name ?? '—',
                'package_count' => (int) $line->package_count,
                'agency_payment' => (float) $line->agency_payment,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, EarningLine>  $lines
     */
    private function aggregateStatus(Collection $lines): string
    {
        if ($lines->isEmpty()) {
            return 'draft';
        }

        $codes = $lines
            ->map(fn (EarningLine $line) => EarningStatusMapper::toUiCode($line->status?->code ?? 'draft'))
            ->unique()
            ->values();

        if ($codes->every(fn (string $code) => $code === 'paid')) {
            return 'paid';
        }

        if ($codes->contains('cancelled') && $codes->count() === 1) {
            return 'cancelled';
        }

        if ($codes->contains('approved')) {
            return 'approved';
        }

        if ($codes->contains('pending')) {
            return 'pending';
        }

        return 'draft';
    }
}
