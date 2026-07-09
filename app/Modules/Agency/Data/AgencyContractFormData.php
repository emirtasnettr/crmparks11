<?php

namespace App\Modules\Agency\Data;

use App\Models\ContractType;

final class AgencyContractFormData
{
    /**
     * @return array<string, string>
     */
    public static function contractTypes(): array
    {
        return ContractType::query()
            ->whereIn('code', ['service', 'commission', 'framework', 'courier_supply'])
            ->orderBy('label')
            ->pluck('label', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function startDateFilters(): array
    {
        return [
            'this_month' => 'Bu Ay Başlayanlar',
            'this_year' => 'Bu Yıl Başlayanlar',
            'last_30_days' => 'Son 30 Gün',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function endDateFilters(): array
    {
        return [
            'expiring_soon' => '30 Gün İçinde Bitenler',
            'this_month' => 'Bu Ay Bitenler',
            'expired' => 'Süresi Dolmuş',
        ];
    }
}
