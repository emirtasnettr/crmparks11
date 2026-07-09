<?php

namespace App\Modules\Finance\Data;

class ProfitabilityFormData
{
    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'all' => 'Tümü',
            'month' => 'Bu Ay',
            'quarter' => 'Bu Çeyrek',
            'year' => 'Bu Yıl',
            'last_6_months' => 'Son 6 Ay',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function pricingModels(): array
    {
        return [
            'per_package' => 'Paket Başı',
            'fixed' => 'Aylık Sabit',
            'hourly' => 'Saatlik',
            'daily' => 'Günlük',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function profitMarginFilters(): array
    {
        return [
            'all' => 'Tümü',
            'high' => 'Yüksek (≥ %25)',
            'medium' => 'Orta (%10 - %24)',
            'low' => 'Düşük (%0 - %9)',
            'negative' => 'Negatif',
        ];
    }

    public static function normalizePricingModel(?string $model): string
    {
        return match ($model) {
            'monthly_fixed' => 'fixed',
            default => $model ?: 'per_package',
        };
    }

    public static function matchesMarginFilter(float $margin, string $filter): bool
    {
        return match ($filter) {
            'high' => $margin >= 25,
            'medium' => $margin >= 10 && $margin < 25,
            'low' => $margin >= 0 && $margin < 10,
            'negative' => $margin < 0,
            default => true,
        };
    }
}
