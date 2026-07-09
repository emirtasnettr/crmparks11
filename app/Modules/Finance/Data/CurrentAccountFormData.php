<?php

namespace App\Modules\Finance\Data;

class CurrentAccountFormData
{
    /**
     * @return array<string, string>
     */
    public static function accountTypes(): array
    {
        return [
            'business' => 'İşletme',
            'courier' => 'Kurye',
            'agency' => 'Acente',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'passive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function balanceStatuses(): array
    {
        return [
            'receivable' => 'Alacaklı',
            'payable' => 'Borçlu',
            'zero' => 'Sıfır',
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
            'debit_note' => 'Borç Dekontu',
            'credit_note' => 'Alacak Dekontu',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function movementTypeLabels(): array
    {
        return array_merge(self::transactionTypes(), [
            'invoice' => 'Fatura',
            'earning' => 'Hakediş',
        ]);
    }

    /**
     * @return array<string, array{debit: bool, credit: bool}>
     */
    public static function movementSides(): array
    {
        return [
            'collection' => ['debit' => false, 'credit' => true],
            'payment' => ['debit' => true, 'credit' => false],
            'debit_note' => ['debit' => true, 'credit' => false],
            'credit_note' => ['debit' => false, 'credit' => true],
            'invoice' => ['debit' => true, 'credit' => false],
            'earning' => ['debit' => false, 'credit' => true],
        ];
    }
}
