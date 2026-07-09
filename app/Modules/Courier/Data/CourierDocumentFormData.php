<?php

namespace App\Modules\Courier\Data;

use App\Models\DocumentCategory;

final class CourierDocumentFormData
{
    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        return DocumentCategory::query()
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
            'valid' => 'Geçerli',
            'expiring_soon' => 'Süresi Yaklaşıyor',
            'expired' => 'Süresi Dolmuş',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function expiryFilters(): array
    {
        return [
            'expiring_soon' => '30 Gün İçinde Dolanlar',
            'expired' => 'Süresi Dolmuş',
            'this_month' => 'Bu Ay Dolanlar',
            'next_3_months' => 'Önümüzdeki 3 Ay',
        ];
    }
}
