<?php

namespace App\Modules\Finance\Data;

class ExpenseFormData
{
    /**
     * @return array<string, string>
     */
    public static function expenseTypes(): array
    {
        return [
            'courier_earning' => 'Kurye Hakedişi',
            'agency_earning' => 'Acente Hakedişi',
            'personnel' => 'Personel',
            'fuel' => 'Yakıt',
            'office' => 'Ofis',
            'software' => 'Yazılım',
            'advertising' => 'Reklam',
            'tax' => 'Vergi',
            'rent' => 'Kira',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentStatuses(): array
    {
        return [
            'paid' => 'Ödendi',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sources(): array
    {
        return [
            'earning' => 'Hakediş',
            'manual' => 'Manuel',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dateRanges(): array
    {
        return [
            'all' => 'Tümü',
            'today' => 'Bugün',
            'week' => 'Bu Hafta',
            'month' => 'Bu Ay',
            'year' => 'Bu Yıl',
        ];
    }
}
