<?php

namespace App\Modules\Courier\Data;

use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessFormData;

class CourierFormData
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function districtsByCity(): array
    {
        return BusinessFormData::districtsByCity();
    }

    /**
     * @return array<int, string>
     */
    public static function cities(): array
    {
        return BusinessFormData::cities();
    }

    /**
     * @return array<string, string>
     */
    public static function courierTypes(): array
    {
        return [
            'independent' => 'Esnaf Kurye',
            'agency' => 'Acente Kuryesi',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return BusinessAssignmentDummyData::agencies();
    }

    /**
     * @return array<string, string>
     */
    public static function vehicleTypes(): array
    {
        return [
            'motorcycle' => 'Motosiklet',
            'car' => 'Otomobil',
            'ebike' => 'Elektrikli Bisiklet',
            'bicycle' => 'Bisiklet',
            'pedestrian' => 'Yaya',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
            'on_leave' => 'İzinli',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function banks(): array
    {
        return [
            'ziraat' => 'Ziraat Bankası',
            'isbank' => 'Türkiye İş Bankası',
            'garanti' => 'Garanti BBVA',
            'akbank' => 'Akbank',
            'yapikredi' => 'Yapı Kredi',
            'halkbank' => 'Halkbank',
            'vakifbank' => 'VakıfBank',
            'denizbank' => 'DenizBank',
            'qnb' => 'QNB Finansbank',
            'teb' => 'TEB',
            'other' => 'Diğer',
        ];
    }
}
