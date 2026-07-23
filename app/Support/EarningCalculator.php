<?php

namespace App\Support;

final class EarningCalculator
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     earning_type: string,
     *     package_count: float|int,
     *     worked_hours: float,
     *     revenue_unit_price: float,
     *     revenue_total: float,
     *     courier_unit_price: float,
     *     courier_total: float,
     *     extra_payment: float,
     *     extra_expense: float,
     *     deduction: float,
     *     net_courier_payment: float,
     *     profit: float,
     *     agency_payment: float
     * }
     */
    public static function fromForm(array $data, bool $courierHasAgency): array
    {
        $pricingModel = (string) ($data['pricing_model'] ?? 'per_package');
        $extraPayment = (float) ($data['extra_income'] ?? $data['extra_payment'] ?? 0);
        $extraExpense = (float) ($data['extra_expense'] ?? 0);
        $deduction = (float) ($data['deduction'] ?? 0);
        $workedHours = round((float) ($data['worked_hours'] ?? $data['hours'] ?? 0), 2);

        if ($pricingModel === 'per_package') {
            $packageCount = (float) ($data['package_count'] ?? 0);
            $revenueUnit = (float) ($data['revenue_unit_price'] ?? 0);
            $courierUnit = (float) ($data['courier_unit_price'] ?? $data['unit_price'] ?? 0);
            $revenueTotal = round($packageCount * $revenueUnit, 2);
            $courierTotal = round($packageCount * $courierUnit, 2);
            $earningType = 'package_based';
        } elseif ($pricingModel === 'hourly') {
            $packageCount = 0;
            $revenueUnit = (float) ($data['revenue_unit_price'] ?? 0);
            $courierUnit = (float) ($data['courier_unit_price'] ?? $data['unit_price'] ?? 0);
            $revenueTotalInput = (float) ($data['revenue_total'] ?? 0);
            $courierTotalInput = (float) ($data['courier_payment'] ?? $data['earning_amount'] ?? 0);

            if ($workedHours > 0) {
                $revenueTotal = round($workedHours * $revenueUnit, 2);
                $courierTotal = round($workedHours * $courierUnit, 2);
            } else {
                // Geriye dönük: saat yoksa doğrudan tutar (eski kayıt / import).
                $revenueTotal = round($revenueTotalInput, 2);
                $courierTotal = round($courierTotalInput, 2);
            }

            $earningType = 'hourly';
        } else {
            $packageCount = 0;
            $workedHours = 0.0;
            $revenueUnit = 0.0;
            $courierUnit = 0.0;
            $revenueTotal = round((float) ($data['revenue_total'] ?? 0), 2);
            $courierTotal = round((float) ($data['courier_payment'] ?? $data['earning_amount'] ?? 0), 2);
            $earningType = match ($pricingModel) {
                'daily' => 'daily',
                default => 'fixed_period',
            };
        }

        $netCourierPayment = round($courierTotal + $extraPayment - $deduction, 2);
        $agencyPayment = $courierHasAgency
            ? round(max(0, (float) ($data['agency_payment'] ?? 0)), 2)
            : 0.0;
        $profit = round($revenueTotal - $courierTotal - $agencyPayment - $extraExpense + $extraPayment - $deduction, 2);

        return [
            'earning_type' => $earningType,
            'package_count' => $packageCount,
            'worked_hours' => $workedHours,
            'revenue_unit_price' => $revenueUnit,
            'revenue_total' => $revenueTotal,
            'courier_unit_price' => $courierUnit,
            'courier_total' => $courierTotal,
            'extra_payment' => $extraPayment,
            'extra_expense' => $extraExpense,
            'deduction' => $deduction,
            'net_courier_payment' => $netCourierPayment,
            'profit' => $profit,
            'agency_payment' => $agencyPayment,
        ];
    }
}
