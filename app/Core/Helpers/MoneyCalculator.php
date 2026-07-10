<?php

namespace App\Core\Helpers;

class MoneyCalculator
{
    public const VAT_LABEL = 'KDV hariç';

    public static function calculatePackageBased(
        int $packageCount,
        float $revenueUnitPrice,
        float $courierUnitPrice,
        float $extraPayment = 0,
        float $deduction = 0,
        float $agencyPayment = 0,
    ): array {
        $revenueTotal = round($packageCount * $revenueUnitPrice, 2);
        $courierTotal = round($packageCount * $courierUnitPrice, 2);
        $netCourierPayment = round($courierTotal + $extraPayment - $deduction, 2);
        $profit = round($revenueTotal - $netCourierPayment - $agencyPayment, 2);

        return compact(
            'revenueTotal',
            'courierTotal',
            'netCourierPayment',
            'profit',
        );
    }

    public static function calculateFixedPeriod(
        float $revenueTotal,
        float $courierTotal,
        float $extraPayment = 0,
        float $deduction = 0,
        float $agencyPayment = 0,
    ): array {
        $netCourierPayment = round($courierTotal + $extraPayment - $deduction, 2);
        $profit = round($revenueTotal - $netCourierPayment - $agencyPayment, 2);

        return compact(
            'revenueTotal',
            'courierTotal',
            'netCourierPayment',
            'profit',
        );
    }

    public static function format(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, ',', '.').' ₺';
    }

    public static function formatVatAmount(float $amount, int $decimals = 2): string
    {
        return self::format($amount, $decimals);
    }

    public static function formatIncludingVat(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, ',', '.').' ₺ (KDV dahil)';
    }
}