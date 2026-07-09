<?php

namespace App\Modules\Business\Data;

final class BusinessActivityFormData
{
    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'business_created' => 'İşletme Oluşturuldu',
            'business_updated' => 'İşletme Güncellendi',
            'contact_added' => 'Yetkili Eklendi',
            'contact_updated' => 'Yetkili Güncellendi',
            'contract_uploaded' => 'Sözleşme Yüklendi',
            'contract_created' => 'Sözleşme Oluşturuldu',
            'courier_assigned' => 'Kurye Atandı',
            'courier_removed' => 'Kurye Ayrıldı',
            'assigned_to_business' => 'Kurye Atandı',
            'removed_from_business' => 'Kurye Ayrıldı',
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_updated' => 'Hakediş Güncellendi',
            'revenue_created' => 'Hakediş Oluşturuldu',
            'collection_created' => 'Tahsilat Yapıldı',
            'document_uploaded' => 'Evrak Yüklendi',
            'document_updated' => 'Evrak Güncellendi',
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
