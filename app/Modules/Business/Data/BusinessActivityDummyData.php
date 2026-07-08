<?php

namespace App\Modules\Business\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class BusinessActivityDummyData
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
            'courier_assigned' => 'Kurye Atandı',
            'courier_removed' => 'Kurye Ayrıldı',
            'earning_created' => 'Hakediş Oluşturuldu',
            'earning_updated' => 'Hakediş Güncellendi',
            'document_uploaded' => 'Evrak Yüklendi',
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

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function users(): array
    {
        return [
            ['id' => 1, 'name' => 'Ahmet Yılmaz'],
            ['id' => 2, 'name' => 'Elif Demir'],
            ['id' => 3, 'name' => 'Mehmet Kaya'],
            ['id' => 4, 'name' => 'Zeynep Arslan'],
            ['id' => 5, 'name' => 'Can Öztürk'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$activities = [
            ['id' => 1, 'occurred_at' => '2026-06-28 16:45:12', 'business_id' => 1, 'business_name' => 'Burger House Gıda Ltd. Şti.', 'action' => 'document_uploaded', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Şube listesi Excel dosyası yüklendi.'],
            ['id' => 2, 'occurred_at' => '2026-06-27 11:20:33', 'business_id' => 8, 'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.', 'action' => 'earning_updated', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '78.189.55.201', 'description' => 'Haziran 2026 hakedişi onaylandı olarak güncellendi.'],
            ['id' => 3, 'occurred_at' => '2026-06-26 09:15:07', 'business_id' => 4, 'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'action' => 'courier_assigned', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Ali Demir kuryesi operasyona atandı.'],
            ['id' => 4, 'occurred_at' => '2026-06-25 14:32:18', 'business_id' => 1, 'business_name' => 'Burger House Gıda Ltd. Şti.', 'action' => 'earning_created', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Haziran 2026 dönemi hakediş kaydı oluşturuldu.'],
            ['id' => 5, 'occurred_at' => '2026-06-24 10:08:55', 'business_id' => 2, 'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.', 'action' => 'contract_uploaded', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Çerçeve sözleşme PDF dosyası sisteme yüklendi.'],
            ['id' => 6, 'occurred_at' => '2026-06-23 17:41:22', 'business_id' => 5, 'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.', 'action' => 'business_updated', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'İletişim telefonu ve fatura adresi güncellendi.'],
            ['id' => 7, 'occurred_at' => '2026-06-22 08:55:40', 'business_id' => 3, 'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'action' => 'contact_added', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '212.156.78.90', 'description' => 'Yeni yetkili Fatma Koç eklendi.'],
            ['id' => 8, 'occurred_at' => '2026-06-21 13:27:19', 'business_id' => 7, 'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.', 'action' => 'courier_removed', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Serkan Yıldız kuryesi atamadan çıkarıldı.'],
            ['id' => 9, 'occurred_at' => '2026-06-20 15:10:03', 'business_id' => 3, 'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'action' => 'document_uploaded', 'user_id' => 5, 'user_name' => 'Can Öztürk', 'ip_address' => '88.247.19.55', 'description' => 'Mağaza cephe fotoğrafı yüklendi.'],
            ['id' => 10, 'occurred_at' => '2026-06-19 11:33:47', 'business_id' => 6, 'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri', 'action' => 'business_created', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Tatlı Diyarı işletmesi sisteme kaydedildi.'],
            ['id' => 11, 'occurred_at' => '2026-06-18 09:22:15', 'business_id' => 4, 'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'action' => 'earning_created', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Mayıs 2026 hakediş kaydı oluşturuldu.'],
            ['id' => 12, 'occurred_at' => '2026-06-17 16:08:31', 'business_id' => 2, 'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.', 'action' => 'contact_updated', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'Genel müdür yetkili bilgileri güncellendi.'],
            ['id' => 13, 'occurred_at' => '2026-06-16 14:45:58', 'business_id' => 1, 'business_name' => 'Burger House Gıda Ltd. Şti.', 'action' => 'courier_assigned', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Emre Çelik kuryesi Kadıköy şubesine atandı.'],
            ['id' => 14, 'occurred_at' => '2026-06-15 10:17:24', 'business_id' => 8, 'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.', 'action' => 'contract_uploaded', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Kurye hizmet sözleşmesi yüklendi.'],
            ['id' => 15, 'occurred_at' => '2026-06-14 08:30:11', 'business_id' => 5, 'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.', 'action' => 'document_uploaded', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'Güncel vergi levhası PDF olarak yüklendi.'],
            ['id' => 16, 'occurred_at' => '2026-06-12 17:55:42', 'business_id' => 4, 'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'action' => 'business_updated', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Çalışma modeli paket başı olarak değiştirildi.'],
            ['id' => 17, 'occurred_at' => '2026-06-11 13:12:08', 'business_id' => 7, 'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.', 'action' => 'earning_updated', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Mayıs 2026 hakedişinde kesinti tutarı güncellendi.'],
            ['id' => 18, 'occurred_at' => '2026-06-10 11:40:36', 'business_id' => 3, 'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'action' => 'courier_assigned', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Burak Aydın kuryesi sabah vardiyasına atandı.'],
            ['id' => 19, 'occurred_at' => '2026-06-08 15:28:19', 'business_id' => 6, 'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri', 'action' => 'contact_added', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'Operasyon sorumlusu Murat Şen eklendi.'],
            ['id' => 20, 'occurred_at' => '2026-06-07 09:05:44', 'business_id' => 2, 'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.', 'action' => 'document_uploaded', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'İmza sirküsü noter onaylı nüsha olarak yüklendi.'],
            ['id' => 21, 'occurred_at' => '2026-06-05 14:18:27', 'business_id' => 1, 'business_name' => 'Burger House Gıda Ltd. Şti.', 'action' => 'business_updated', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Marka adı ve logo bilgileri güncellendi.'],
            ['id' => 22, 'occurred_at' => '2026-06-03 16:33:51', 'business_id' => 4, 'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'action' => 'courier_removed', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Volkan Özdemir kuryesi operasyondan ayrıldı.'],
            ['id' => 23, 'occurred_at' => '2026-06-01 10:22:08', 'business_id' => 5, 'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.', 'action' => 'earning_created', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Mayıs 2026 aylık sabit hakediş kaydı oluşturuldu.'],
            ['id' => 24, 'occurred_at' => '2026-05-29 13:47:15', 'business_id' => 8, 'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.', 'action' => 'business_created', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Taze Manav işletmesi sisteme eklendi.'],
            ['id' => 25, 'occurred_at' => '2026-05-27 11:15:39', 'business_id' => 3, 'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'action' => 'contract_uploaded', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Hizmet sözleşmesi 2026-2027 dönemi yüklendi.'],
            ['id' => 26, 'occurred_at' => '2026-05-25 08:40:22', 'business_id' => 7, 'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.', 'action' => 'document_uploaded', 'user_id' => 5, 'user_name' => 'Can Öztürk', 'ip_address' => '88.247.19.55', 'description' => 'Şube açılış fotoğrafları PNG formatında yüklendi.'],
            ['id' => 27, 'occurred_at' => '2026-05-23 17:12:44', 'business_id' => 2, 'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.', 'action' => 'courier_assigned', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Kemal Arslan kuryesi Beşiktaş şubesine atandı.'],
            ['id' => 28, 'occurred_at' => '2026-05-21 14:55:17', 'business_id' => 6, 'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri', 'action' => 'earning_created', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Nisan 2026 paket başı hakediş kaydı oluşturuldu.'],
            ['id' => 29, 'occurred_at' => '2026-05-19 10:08:33', 'business_id' => 1, 'business_name' => 'Burger House Gıda Ltd. Şti.', 'action' => 'contact_updated', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'Muhasebe yetkilisi e-posta adresi güncellendi.'],
            ['id' => 30, 'occurred_at' => '2026-05-17 15:33:28', 'business_id' => 4, 'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'action' => 'document_uploaded', 'user_id' => 5, 'user_name' => 'Can Öztürk', 'ip_address' => '88.247.19.55', 'description' => 'Depo fotoğrafları ZIP arşivi yüklendi.'],
            ['id' => 31, 'occurred_at' => '2026-05-15 09:27:51', 'business_id' => 5, 'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.', 'action' => 'courier_assigned', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Deniz Korkmaz kuryesi Karşıyaka şubesine atandı.'],
            ['id' => 32, 'occurred_at' => '2026-05-13 12:44:06', 'business_id' => 3, 'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'action' => 'earning_updated', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Nisan 2026 hakedişi ödendi olarak işaretlendi.'],
            ['id' => 33, 'occurred_at' => '2026-05-11 16:19:38', 'business_id' => 7, 'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.', 'action' => 'contact_added', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'Satın alma yetkilisi Hakan Güneş eklendi.'],
            ['id' => 34, 'occurred_at' => '2026-05-09 11:52:14', 'business_id' => 2, 'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.', 'action' => 'earning_created', 'user_id' => 4, 'user_name' => 'Zeynep Arslan', 'ip_address' => '85.105.42.118', 'description' => 'Nisan 2026 hakediş kaydı oluşturuldu.'],
            ['id' => 35, 'occurred_at' => '2026-05-07 08:18:47', 'business_id' => 1, 'business_name' => 'Burger House Gıda Ltd. Şti.', 'action' => 'business_created', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Burger House işletmesi sisteme kaydedildi.'],
            ['id' => 36, 'occurred_at' => '2026-05-05 14:36:22', 'business_id' => 6, 'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri', 'action' => 'contract_uploaded', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'Hizmet sözleşmesi imzalı nüsha yüklendi.'],
            ['id' => 37, 'occurred_at' => '2026-05-03 10:41:55', 'business_id' => 8, 'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.', 'action' => 'contact_added', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'Mağaza müdürü Selin Ak eklendi.'],
            ['id' => 38, 'occurred_at' => '2026-05-01 17:25:08', 'business_id' => 4, 'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.', 'action' => 'business_created', 'user_id' => 1, 'user_name' => 'Ahmet Yılmaz', 'ip_address' => '176.88.12.67', 'description' => 'HızlıAl işletmesi sisteme kaydedildi.'],
            ['id' => 39, 'occurred_at' => '2026-04-28 13:08:41', 'business_id' => 5, 'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.', 'action' => 'courier_removed', 'user_id' => 3, 'user_name' => 'Mehmet Kaya', 'ip_address' => '192.168.1.45', 'description' => 'Onur Tekin kuryesi atamadan çıkarıldı.'],
            ['id' => 40, 'occurred_at' => '2026-04-25 09:33:16', 'business_id' => 3, 'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.', 'action' => 'business_updated', 'user_id' => 2, 'user_name' => 'Elif Demir', 'ip_address' => '95.70.33.144', 'description' => 'İlçe ve mahalle bilgileri güncellendi.'],
        ];

        return collect($activities)
            ->map(fn (array $activity) => self::enrich($activity))
            ->sortByDesc('occurred_at')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $activity
     * @return array<string, mixed>
     */
    public static function enrich(array $activity): array
    {
        $occurredAt = Carbon::parse($activity['occurred_at']);

        return array_merge($activity, [
            'action_label' => self::actionTypes()[$activity['action']] ?? $activity['action'],
            'occurred_at_formatted' => $occurredAt->format('d.m.Y H:i'),
            'occurred_at_date' => $occurredAt->format('Y-m-d'),
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function businesses(): array
    {
        return BusinessContactDummyData::businesses();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        $today = Carbon::today();

        return collect(self::all())
            ->filter(function (array $activity) use ($filters, $today) {
                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $activity['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['user_id']) && $filters['user_id'] !== 'all') {
                    if ((int) $activity['user_id'] !== (int) $filters['user_id']) {
                        return false;
                    }
                }

                if (! empty($filters['action']) && $filters['action'] !== 'all') {
                    if ($activity['action'] !== $filters['action']) {
                        return false;
                    }
                }

                if (! empty($filters['date_range']) && $filters['date_range'] !== 'all') {
                    $occurredAt = Carbon::parse($activity['occurred_at']);

                    $matches = match ($filters['date_range']) {
                        'last_7_days' => $occurredAt->gte($today->copy()->subDays(7)),
                        'last_30_days' => $occurredAt->gte($today->copy()->subDays(30)),
                        'this_month' => $occurredAt->isSameMonth($today),
                        'last_3_months' => $occurredAt->gte($today->copy()->subMonths(3)),
                        'this_year' => $occurredAt->year === $today->year,
                        default => true,
                    };

                    if (! $matches) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }
}
