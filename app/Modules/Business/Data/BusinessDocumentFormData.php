<?php

namespace App\Modules\Business\Data;

use App\Models\DocumentCategory;

final class BusinessDocumentFormData
{
    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        return DocumentCategory::query()
            ->whereIn('code', [
                'contract',
                'tax_plate',
                'signature_circular',
                'activity_certificate',
                'trade_registry',
                'other',
            ])
            ->orderBy('label')
            ->pluck('label', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'pending' => 'Beklemede',
            'expired' => 'Süresi Doldu',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
            'this_month' => 'Bu Ay',
            'last_3_months' => 'Son 3 Ay',
            'this_year' => 'Bu Yıl',
        ];
    }
}
