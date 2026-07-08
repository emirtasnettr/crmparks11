<?php

namespace App\Modules\Agency\Data;

class AgencyContactDummyData
{
    /**
     * @return array<int, string>
     */
    public static function titles(): array
    {
        return [
            'Firma Sahibi',
            'Operasyon Müdürü',
            'Finans Sorumlusu',
            'İnsan Kaynakları',
            'Muhasebe Yetkilisi',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function agencies(): array
    {
        return AgencyDummyData::options();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return collect(self::raw())
            ->map(fn (array $contact) => self::enrich($contact))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        $agencyNames = collect(self::agencies())->keyBy('id');

        $records = [
            ['id' => 1, 'agency_id' => 1, 'first_name' => 'Serkan', 'last_name' => 'Yılmaz', 'title' => 'Firma Sahibi', 'phone' => '0532 401 01 01', 'email' => 'serkan@hizlikurye.com', 'is_default' => true, 'status' => 'active', 'notes' => 'Ana iletişim noktası.'],
            ['id' => 2, 'agency_id' => 1, 'first_name' => 'Deniz', 'last_name' => 'Aksoy', 'title' => 'Operasyon Müdürü', 'phone' => '0533 401 01 02', 'email' => 'deniz@hizlikurye.com', 'is_default' => false, 'status' => 'active', 'notes' => null],
            ['id' => 3, 'agency_id' => 2, 'first_name' => 'Ayşe', 'last_name' => 'Korkmaz', 'title' => 'Firma Sahibi', 'phone' => '0532 402 02 01', 'email' => 'ayse@metrologistik.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 4, 'agency_id' => 2, 'first_name' => 'Burak', 'last_name' => 'Çetin', 'title' => 'Finans Sorumlusu', 'phone' => '0533 402 02 02', 'email' => 'burak@metrologistik.com', 'is_default' => false, 'status' => 'active', 'notes' => 'Fatura ve hakediş süreçlerinden sorumlu.'],
            ['id' => 5, 'agency_id' => 3, 'first_name' => 'Mehmet', 'last_name' => 'Arslan', 'title' => 'Firma Sahibi', 'phone' => '0532 403 03 01', 'email' => 'mehmet@expressdagitim.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 6, 'agency_id' => 4, 'first_name' => 'Fatma', 'last_name' => 'Çelik', 'title' => 'Firma Sahibi', 'phone' => '0532 404 04 01', 'email' => 'fatma@anadolukurye.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 7, 'agency_id' => 4, 'first_name' => 'Hakan', 'last_name' => 'Şahin', 'title' => 'Operasyon Müdürü', 'phone' => '0533 404 04 02', 'email' => 'hakan@anadolukurye.com', 'is_default' => false, 'status' => 'active', 'notes' => null],
            ['id' => 8, 'agency_id' => 4, 'first_name' => 'Selin', 'last_name' => 'Yıldız', 'title' => 'İnsan Kaynakları', 'phone' => '0534 404 04 03', 'email' => 'selin@anadolukurye.com', 'is_default' => false, 'status' => 'inactive', 'notes' => 'İzinli.'],
            ['id' => 9, 'agency_id' => 5, 'first_name' => 'Oğuz', 'last_name' => 'Demir', 'title' => 'Firma Sahibi', 'phone' => '0532 405 05 01', 'email' => 'oguz@bursaekspres.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 10, 'agency_id' => 5, 'first_name' => 'Zeynep', 'last_name' => 'Koç', 'title' => 'Muhasebe Yetkilisi', 'phone' => '0533 405 05 02', 'email' => 'zeynep@bursaekspres.com', 'is_default' => false, 'status' => 'active', 'notes' => null],
            ['id' => 11, 'agency_id' => 6, 'first_name' => 'Deniz', 'last_name' => 'Aydın', 'title' => 'Operasyon Müdürü', 'phone' => '0532 406 06 01', 'email' => 'deniz@akdenizdagitim.com', 'is_default' => true, 'status' => 'active', 'notes' => 'Onay sürecindeki acente.'],
            ['id' => 12, 'agency_id' => 7, 'first_name' => 'Hakan', 'last_name' => 'Şahin', 'title' => 'Firma Sahibi', 'phone' => '0532 407 07 01', 'email' => 'hakan@cukurovakurye.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 13, 'agency_id' => 8, 'first_name' => 'Zeynep', 'last_name' => 'Koç', 'title' => 'Finans Sorumlusu', 'phone' => '0532 408 08 01', 'email' => 'zeynep@gaziantephizli.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 14, 'agency_id' => 9, 'first_name' => 'Burak', 'last_name' => 'Tunç', 'title' => 'Firma Sahibi', 'phone' => '0532 409 09 01', 'email' => 'burak@konyamerkez.com', 'is_default' => true, 'status' => 'inactive', 'notes' => 'Acente pasif durumda.'],
            ['id' => 15, 'agency_id' => 10, 'first_name' => 'Selin', 'last_name' => 'Erdoğan', 'title' => 'Firma Sahibi', 'phone' => '0532 410 10 01', 'email' => 'selin@mersinsahil.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 16, 'agency_id' => 11, 'first_name' => 'Tolga', 'last_name' => 'Uçar', 'title' => 'Operasyon Müdürü', 'phone' => '0532 411 11 01', 'email' => 'tolga@kayseridagitim.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 17, 'agency_id' => 11, 'first_name' => 'Can', 'last_name' => 'Öztürk', 'title' => 'İnsan Kaynakları', 'phone' => '0533 411 11 02', 'email' => 'can@kayseridagitim.com', 'is_default' => false, 'status' => 'active', 'notes' => null],
            ['id' => 18, 'agency_id' => 12, 'first_name' => 'Emre', 'last_name' => 'Polat', 'title' => 'Firma Sahibi', 'phone' => '0532 412 12 01', 'email' => 'emre@eskisehirekspres.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 19, 'agency_id' => 13, 'first_name' => 'Gizem', 'last_name' => 'Yalçın', 'title' => 'Muhasebe Yetkilisi', 'phone' => '0532 413 13 01', 'email' => 'gizem@karadenizlojistik.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 20, 'agency_id' => 14, 'first_name' => 'Kaan', 'last_name' => 'Başaran', 'title' => 'Firma Sahibi', 'phone' => '0532 414 14 01', 'email' => 'kaan@samsunhizli.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 21, 'agency_id' => 15, 'first_name' => 'Leyla', 'last_name' => 'Güneş', 'title' => 'Operasyon Müdürü', 'phone' => '0532 415 15 01', 'email' => 'leyla@denizlipaket.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 22, 'agency_id' => 16, 'first_name' => 'Mert', 'last_name' => 'Akın', 'title' => 'Firma Sahibi', 'phone' => '0532 416 16 01', 'email' => 'mert@muglaturizm.com', 'is_default' => true, 'status' => 'active', 'notes' => 'Turizm sezonu yoğunluğu bildirimleri bu kişiye gider.'],
            ['id' => 23, 'agency_id' => 17, 'first_name' => 'Nazlı', 'last_name' => 'Tekin', 'title' => 'Finans Sorumlusu', 'phone' => '0532 417 17 01', 'email' => 'nazli@kocaelisanayi.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 24, 'agency_id' => 17, 'first_name' => 'Onur', 'last_name' => 'Sarı', 'title' => 'Operasyon Müdürü', 'phone' => '0533 417 17 02', 'email' => 'onur@kocaelisanayi.com', 'is_default' => false, 'status' => 'active', 'notes' => null],
            ['id' => 25, 'agency_id' => 18, 'first_name' => 'Pınar', 'last_name' => 'Vural', 'title' => 'Firma Sahibi', 'phone' => '0532 418 18 01', 'email' => 'pinar@sakaryaekspres.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 26, 'agency_id' => 20, 'first_name' => 'Rıza', 'last_name' => 'Duman', 'title' => 'Operasyon Müdürü', 'phone' => '0532 420 20 01', 'email' => 'riza@tekirdagmarmara.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 27, 'agency_id' => 21, 'first_name' => 'Seda', 'last_name' => 'Işık', 'title' => 'Muhasebe Yetkilisi', 'phone' => '0532 421 21 01', 'email' => 'seda@hatayguney.com', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 28, 'agency_id' => 25, 'first_name' => 'Yasin', 'last_name' => 'Sezer', 'title' => 'Firma Sahibi', 'phone' => '0532 425 25 01', 'email' => 'yasin@aydinege.com', 'is_default' => true, 'status' => 'active', 'notes' => 'Bölgenin en büyük acentesi.'],
        ];

        return collect($records)
            ->map(function (array $contact) use ($agencyNames) {
                $contact['agency_name'] = $agencyNames[$contact['agency_id']]['name'] ?? '—';

                return $contact;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $contact
     * @return array<string, mixed>
     */
    public static function enrich(array $contact): array
    {
        return array_merge($contact, [
            'uuid' => 'agct-'.str_pad((string) $contact['id'], 3, '0', STR_PAD_LEFT),
            'full_name' => trim($contact['first_name'].' '.$contact['last_name']),
            'status_label' => $contact['status'] === 'active' ? 'Aktif' : 'Pasif',
        ]);
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $contact) {
            if ($contact['id'] === $id) {
                return $contact;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public static function summarize(array $filters = []): array
    {
        $items = self::filter($filters);

        return [
            'total' => count($items),
            'active' => collect($items)->where('status', 'active')->count(),
            'default' => collect($items)->where('is_default', true)->count(),
            'inactive' => collect($items)->where('status', 'inactive')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $filters): array
    {
        return collect(self::all())
            ->filter(function (array $contact) use ($filters) {
                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $contact['full_name'],
                        $contact['phone'],
                        $contact['email'] ?? '',
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['agency_id']) && $filters['agency_id'] !== 'all') {
                    if ((int) $contact['agency_id'] !== (int) $filters['agency_id']) {
                        return false;
                    }
                }

                if (! empty($filters['title']) && $filters['title'] !== 'all') {
                    if ($contact['title'] !== $filters['title']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($contact['status'] !== $filters['status']) {
                        return false;
                    }
                }

                return true;
            })
            ->values()
            ->all();
    }
}
