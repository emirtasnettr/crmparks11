<?php

namespace App\Modules\Finance\Data;

class PaymentFormData
{
    /**
     * @return array<string, string>
     */
    public static function recipientTypes(): array
    {
        return [
            'courier' => 'Kurye',
            'agency' => 'Acente',
            'personnel' => 'Personel',
            'supplier' => 'Tedarikçi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentStatuses(): array
    {
        return [
            'paid' => 'Ödendi',
            'partial' => 'Kısmi Ödendi',
            'pending' => 'Bekliyor',
            'cancelled' => 'İptal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethods(): array
    {
        return [
            'bank_transfer' => 'Banka Havalesi',
            'eft' => 'EFT',
            'fast' => 'FAST',
            'cash' => 'Nakit',
            'credit_card' => 'Kredi Kartı',
            'offset' => 'Mahsup',
            'other' => 'Diğer',
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
     * @return array<int, array{id: int, name: string}>
     */
    public static function personnel(): array
    {
        return [
            ['id' => 1, 'name' => 'Ayşe Yılmaz'],
            ['id' => 2, 'name' => 'Mehmet Demir'],
            ['id' => 3, 'name' => 'Zeynep Kaya'],
            ['id' => 4, 'name' => 'Can Öztürk'],
            ['id' => 5, 'name' => 'Elif Şahin'],
            ['id' => 6, 'name' => 'Burak Aydın'],
            ['id' => 7, 'name' => 'Selin Arslan'],
            ['id' => 8, 'name' => 'Emre Çelik'],
            ['id' => 9, 'name' => 'Deniz Koç'],
            ['id' => 10, 'name' => 'Gizem Polat'],
            ['id' => 11, 'name' => 'Hakan Yıldız'],
            ['id' => 12, 'name' => 'İrem Güneş'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function suppliers(): array
    {
        return [
            ['id' => 1, 'name' => 'Atlas Lojistik Ltd. Şti.'],
            ['id' => 2, 'name' => 'Net Yazılım A.Ş.'],
            ['id' => 3, 'name' => 'Ege Yakıt Dağıtım'],
            ['id' => 4, 'name' => 'Merkez Ofis Malzemeleri'],
            ['id' => 5, 'name' => 'Dijital Reklam Ajansı'],
            ['id' => 6, 'name' => 'Güven Sigorta Aracılık'],
            ['id' => 7, 'name' => 'Tekno Bilişim Çözümleri'],
            ['id' => 8, 'name' => 'Anadolu Kırtasiye'],
            ['id' => 9, 'name' => 'Filo Bakım Servisi'],
            ['id' => 10, 'name' => 'Kurumsal Temizlik Hizmetleri'],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function staticRecipients(string $type): array
    {
        return match ($type) {
            'personnel' => self::personnel(),
            'supplier' => self::suppliers(),
            default => [],
        };
    }

    public static function staticRecipientName(string $type, int $id): ?string
    {
        $recipient = collect(self::staticRecipients($type))->firstWhere('id', $id);

        return $recipient['name'] ?? null;
    }
}
