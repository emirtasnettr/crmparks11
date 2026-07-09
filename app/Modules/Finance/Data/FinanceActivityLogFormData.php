<?php

namespace App\Modules\Finance\Data;

class FinanceActivityLogFormData
{
    /**
     * @return array<string, string>
     */
    public static function modules(): array
    {
        return [
            'revenues' => 'Gelirler',
            'expenses' => 'Giderler',
            'collections' => 'Tahsilatlar',
            'payments' => 'Ödemeler',
            'invoices' => 'Faturalar',
            'current_accounts' => 'Cari Hesaplar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function actionTypes(): array
    {
        return [
            'revenue_created' => 'Kayıt Oluşturuldu',
            'expense_created' => 'Kayıt Oluşturuldu',
            'collection_created' => 'Tahsilat Yapıldı',
            'payment_created' => 'Ödeme Yapıldı',
            'invoice_created' => 'Fatura Kesildi',
            'current_account_created' => 'Kayıt Oluşturuldu',
            'current_account_movement_created' => 'Cari Hareketi Oluşturuldu',
        ];
    }

    /**
     * @return array<string>
     */
    public static function financeActions(): array
    {
        return array_keys(self::actionTypes());
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'success' => 'Başarılı',
            'warning' => 'Uyarı',
            'error' => 'Hata',
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

    public static function moduleForSubjectType(?string $subjectType): ?string
    {
        return match ($subjectType) {
            \App\Modules\Finance\Models\FinanceRevenue::class => 'revenues',
            \App\Modules\Finance\Models\FinanceExpense::class => 'expenses',
            \App\Modules\Finance\Models\FinanceCollection::class => 'collections',
            \App\Modules\Finance\Models\FinancePayment::class => 'payments',
            \App\Modules\Finance\Models\FinanceInvoice::class => 'invoices',
            \App\Modules\Finance\Models\CurrentAccount::class,
            \App\Modules\Finance\Models\CurrentAccountMovement::class => 'current_accounts',
            default => null,
        };
    }
}
