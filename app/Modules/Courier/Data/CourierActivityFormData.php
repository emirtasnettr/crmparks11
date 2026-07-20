<?php

namespace App\Modules\Courier\Data;

final class CourierActivityFormData
{
    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'courier_created' => 'Kurye Oluşturuldu',
            'courier_updated' => 'Kurye Güncellendi',
            'courier_password_changed' => 'Giriş Şifresi Değiştirildi',
            'courier_deactivated' => 'Kurye Pasife Alındı',
            'courier_activated' => 'Kurye Aktifleştirildi',
            'document_uploaded' => 'Belge Yüklendi',
            'document_updated' => 'Belge Güncellendi',
            'vehicle_added' => 'Araç Eklendi',
            'vehicle_updated' => 'Araç Güncellendi',
            'bank_account_added' => 'Banka Hesabı Eklendi',
            'bank_account_updated' => 'Banka Hesabı Güncellendi',
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_updated' => 'Hakediş Güncellendi',
            'assigned_to_business' => 'İşletmeye Atandı',
            'removed_from_business' => 'İşletmeden Ayrıldı',
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
