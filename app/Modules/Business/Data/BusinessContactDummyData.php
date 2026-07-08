<?php

namespace App\Modules\Business\Data;

use App\Support\DemoData;

use App\Modules\Business\Models\Business;

class BusinessContactDummyData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return [
            [
                'id' => 1,
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'full_name' => 'Mehmet Yılmaz',
                'title' => 'İşletme Sahibi',
                'phone' => '0532 111 22 33',
                'email' => 'mehmet@burgerhouse.com',
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'id' => 2,
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'full_name' => 'Ayşe Demir',
                'title' => 'Operasyon Müdürü',
                'phone' => '0533 222 33 44',
                'email' => 'ayse@burgerhouse.com',
                'is_default' => false,
                'status' => 'active',
            ],
            [
                'id' => 3,
                'business_id' => 2,
                'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
                'full_name' => 'Can Öztürk',
                'title' => 'Restoran Müdürü',
                'phone' => '0542 333 44 55',
                'email' => 'can@napolipizza.com',
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'id' => 4,
                'business_id' => 3,
                'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
                'full_name' => 'Fatma Kaya',
                'title' => 'Şube Müdürü',
                'phone' => '0555 444 55 66',
                'email' => 'fatma@yesilmarket.com',
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'id' => 5,
                'business_id' => 3,
                'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
                'full_name' => 'Ali Çelik',
                'title' => 'Muhasebe Yetkilisi',
                'phone' => '0536 555 66 77',
                'email' => 'ali@yesilmarket.com',
                'is_default' => false,
                'status' => 'inactive',
            ],
            [
                'id' => 6,
                'business_id' => 4,
                'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
                'full_name' => 'Zeynep Arslan',
                'title' => 'Operasyon Müdürü',
                'phone' => '0544 666 77 88',
                'email' => 'zeynep@hizlial.com',
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'id' => 7,
                'business_id' => 5,
                'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.',
                'full_name' => 'Burak Şahin',
                'title' => 'İşletme Sahibi',
                'phone' => '0532 777 88 99',
                'email' => 'burak@kahveduragi.com',
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'id' => 8,
                'business_id' => 6,
                'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
                'full_name' => 'Elif Yıldız',
                'title' => 'Restoran Müdürü',
                'phone' => '0533 888 99 00',
                'email' => 'elif@tatlidiyari.com',
                'is_default' => true,
                'status' => 'inactive',
            ],
            [
                'id' => 9,
                'business_id' => 7,
                'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.',
                'full_name' => 'Hakan Koç',
                'title' => 'Şube Müdürü',
                'phone' => '0542 999 00 11',
                'email' => 'hakan@ustakasap.com',
                'is_default' => true,
                'status' => 'active',
            ],
            [
                'id' => 10,
                'business_id' => 8,
                'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.',
                'full_name' => 'Selin Aydın',
                'title' => 'Muhasebe Yetkilisi',
                'phone' => '0555 000 11 22',
                'email' => 'selin@tazemanav.com',
                'is_default' => false,
                'status' => 'active',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function titles(): array
    {
        return [
            'İşletme Sahibi',
            'Şube Müdürü',
            'Operasyon Müdürü',
            'Restoran Müdürü',
            'Muhasebe Yetkilisi',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function businesses(): array
    {
        return Business::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name'])
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->company_name,
            ])
            ->values()
            ->all();
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

                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $contact['business_id'] !== (int) $filters['business_id']) {
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
