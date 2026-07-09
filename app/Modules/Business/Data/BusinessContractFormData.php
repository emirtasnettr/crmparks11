<?php

namespace App\Modules\Business\Data;

use App\Models\ContractType;

final class BusinessContractFormData
{
    /**
     * @return array<string, string>
     */
    public static function contractTypes(): array
    {
        return ContractType::query()
            ->whereIn('code', ['service', 'courier', 'agency', 'framework'])
            ->orderBy('label')
            ->pluck('label', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function storedStatuses(): array
    {
        return [
            'draft' => 'Taslak',
            'active' => 'Aktif',
        ];
    }
}
