<?php

namespace App\Modules\Finance\Data;

class CashFlowFormData
{
    /**
     * @return array<string, string>
     */
    public static function periods(): array
    {
        return [
            'today' => 'Bugün',
            'week' => 'Bu Hafta',
            'month' => 'Bu Ay',
            'year' => 'Bu Yıl',
            'custom' => 'Özel Tarih Aralığı',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function transactionTypes(): array
    {
        return [
            'collection' => 'Tahsilat',
            'payment' => 'Ödeme',
            'revenue' => 'Gelir',
            'expense' => 'Gider',
            'cash_in' => 'Manuel Nakit Girişi',
            'cash_out' => 'Manuel Nakit Çıkışı',
            'offset' => 'Mahsup',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sourceTypes(): array
    {
        return [
            'revenue' => 'Gelir',
            'expense' => 'Gider',
            'collection' => 'Tahsilat',
            'payment' => 'Ödeme',
            'earning' => 'Hakediş',
            'invoice' => 'Fatura',
            'manual' => 'Manuel',
        ];
    }
}
