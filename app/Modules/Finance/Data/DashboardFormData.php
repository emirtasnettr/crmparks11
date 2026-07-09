<?php

namespace App\Modules\Finance\Data;

class DashboardFormData
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

    public static function transactionStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Tamamlandı',
            'pending' => 'Bekliyor',
            'overdue' => 'Gecikmiş',
            'approval' => 'Onay Bekliyor',
            default => $status,
        };
    }

    public static function paymentStatusLabel(string $status): string
    {
        return PaymentFormData::paymentStatuses()[$status] ?? $status;
    }
}
