<?php

namespace App\Modules\Agency\Data;

final class AgencyActivityFormData
{
    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'agency_created' => 'Acente Oluşturuldu',
            'agency_updated' => 'Acente Güncellendi',
            'contact_added' => 'Yetkili Eklendi',
            'contact_updated' => 'Yetkili Güncellendi',
            'courier_assigned' => 'Kurye Atandı',
            'courier_removed' => 'Kurye Ayrıldı',
            'courier_created' => 'Kurye Atandı',
            'courier_updated' => 'Kurye Güncellendi',
            'courier_deactivated' => 'Kurye Pasife Alındı',
            'courier_activated' => 'Kurye Aktifleştirildi',
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_updated' => 'Hakediş Güncellendi',
            'expense_created' => 'Hakediş Oluşturuldu',
            'expense_updated' => 'Hakediş Güncellendi',
            'document_uploaded' => 'Evrak Yüklendi',
            'document_updated' => 'Evrak Güncellendi',
            'contract_created' => 'Sözleşme Oluşturuldu',
            'contract_updated' => 'Sözleşme Güncellendi',
            'contract_renewed' => 'Sözleşme Yenilendi',
            'agency_deactivated' => 'Acente Pasife Alındı',
            'agency_activated' => 'Acente Aktifleştirildi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'today' => 'Bugün',
            'this_week' => 'Bu Hafta',
            'this_month' => 'Bu Ay',
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
            'this_year' => 'Bu Yıl',
        ];
    }
}
