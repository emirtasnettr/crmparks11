<?php

namespace App\Modules\Business\Data;

use App\Support\DemoData;
use Carbon\Carbon;

class BusinessDocumentDummyData
{
    /**
     * @return array<string, string>
     */
    public static function documentTypes(): array
    {
        return [
            'contract' => 'Sözleşme',
            'tax_plate' => 'Vergi Levhası',
            'signature_circular' => 'İmza Sirküsü',
            'activity_certificate' => 'Faaliyet Belgesi',
            'trade_registry' => 'Ticaret Sicil Gazetesi',
            'other' => 'Diğer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'pending' => 'Beklemede',
            'expired' => 'Süresi Doldu',
            'archived' => 'Arşivlendi',
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
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

$documents = [
            [
                'id' => 1,
                'uuid' => 'doc-001',
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'name' => 'Hizmet Sözleşmesi 2026',
                'document_type' => 'contract',
                'file_name' => 'burger-house-hizmet-sozlesmesi-2026.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 2_457_600,
                'uploaded_at' => '2026-01-10',
                'uploaded_by' => 'Ahmet Yılmaz',
                'status' => 'active',
                'description' => 'Yıllık kurye hizmet sözleşmesi.',
            ],
            [
                'id' => 2,
                'uuid' => 'doc-002',
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'name' => 'Vergi Levhası',
                'document_type' => 'tax_plate',
                'file_name' => 'burger-house-vergi-levhasi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 524_288,
                'uploaded_at' => '2025-11-22',
                'uploaded_by' => 'Elif Demir',
                'status' => 'active',
                'description' => null,
            ],
            [
                'id' => 3,
                'uuid' => 'doc-003',
                'business_id' => 2,
                'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
                'name' => 'Çerçeve Sözleşme',
                'document_type' => 'contract',
                'file_name' => 'napoli-pizza-cerceve-sozlesme.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 3_145_728,
                'uploaded_at' => '2026-01-15',
                'uploaded_by' => 'Mehmet Kaya',
                'status' => 'active',
                'description' => 'Çerçeve sözleşme — imzalı nüsha.',
            ],
            [
                'id' => 4,
                'uuid' => 'doc-004',
                'business_id' => 2,
                'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
                'name' => 'İmza Sirküsü',
                'document_type' => 'signature_circular',
                'file_name' => 'napoli-pizza-imza-sirkuesi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 892_416,
                'uploaded_at' => '2025-12-05',
                'uploaded_by' => 'Elif Demir',
                'status' => 'active',
                'description' => 'Noter onaylı imza sirküsü.',
            ],
            [
                'id' => 5,
                'uuid' => 'doc-005',
                'business_id' => 3,
                'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
                'name' => 'Faaliyet Belgesi',
                'document_type' => 'activity_certificate',
                'file_name' => 'yesil-market-faaliyet-belgesi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 655_360,
                'uploaded_at' => '2026-02-28',
                'uploaded_by' => 'Zeynep Arslan',
                'status' => 'pending',
                'description' => 'Yenileme başvurusu yapıldı, onay bekleniyor.',
            ],
            [
                'id' => 6,
                'uuid' => 'doc-006',
                'business_id' => 3,
                'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
                'name' => 'Ticaret Sicil Gazetesi',
                'document_type' => 'trade_registry',
                'file_name' => 'yesil-market-ticaret-sicil.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 1_048_576,
                'uploaded_at' => '2025-08-14',
                'uploaded_by' => 'Ahmet Yılmaz',
                'status' => 'active',
                'description' => null,
            ],
            [
                'id' => 7,
                'uuid' => 'doc-007',
                'business_id' => 4,
                'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
                'name' => 'Kurye Operasyon Sözleşmesi',
                'document_type' => 'contract',
                'file_name' => 'hizlial-kurye-operasyon.docx',
                'file_extension' => 'docx',
                'file_size_bytes' => 1_572_864,
                'uploaded_at' => '2026-03-01',
                'uploaded_by' => 'Mehmet Kaya',
                'status' => 'active',
                'description' => 'Word formatında taslak sözleşme.',
            ],
            [
                'id' => 8,
                'uuid' => 'doc-008',
                'business_id' => 4,
                'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
                'name' => 'Aylık Paket Raporu Şablonu',
                'document_type' => 'other',
                'file_name' => 'hizlial-paket-raporu-sablonu.xlsx',
                'file_extension' => 'xlsx',
                'file_size_bytes' => 245_760,
                'uploaded_at' => '2026-02-10',
                'uploaded_by' => 'Zeynep Arslan',
                'status' => 'active',
                'description' => 'Operasyon ekibi için rapor şablonu.',
            ],
            [
                'id' => 9,
                'uuid' => 'doc-009',
                'business_id' => 4,
                'business_name' => 'HızlıAl E-Ticaret ve Lojistik A.Ş.',
                'name' => 'Depo Fotoğrafları',
                'document_type' => 'other',
                'file_name' => 'hizlial-depo-fotograflari.zip',
                'file_extension' => 'zip',
                'file_size_bytes' => 15_728_640,
                'uploaded_at' => '2026-01-20',
                'uploaded_by' => 'Can Öztürk',
                'status' => 'active',
                'description' => 'Depo ve araç fotoğrafları arşivi.',
            ],
            [
                'id' => 10,
                'uuid' => 'doc-010',
                'business_id' => 5,
                'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.',
                'name' => 'Vergi Levhası',
                'document_type' => 'tax_plate',
                'file_name' => 'kahve-duragi-vergi-levhasi.jpg',
                'file_extension' => 'jpg',
                'file_size_bytes' => 1_835_008,
                'uploaded_at' => '2025-06-01',
                'uploaded_by' => 'Elif Demir',
                'status' => 'expired',
                'description' => 'Eski vergi levhası — yenisi yüklendi.',
            ],
            [
                'id' => 11,
                'uuid' => 'doc-011',
                'business_id' => 5,
                'business_name' => 'Kahve Durağı İşletmecilik Ltd. Şti.',
                'name' => 'Güncel Vergi Levhası',
                'document_type' => 'tax_plate',
                'file_name' => 'kahve-duragi-vergi-levhasi-2026.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 612_352,
                'uploaded_at' => '2026-05-28',
                'uploaded_by' => 'Elif Demir',
                'status' => 'active',
                'description' => null,
            ],
            [
                'id' => 12,
                'uuid' => 'doc-012',
                'business_id' => 6,
                'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
                'name' => 'Hizmet Sözleşmesi',
                'document_type' => 'contract',
                'file_name' => 'tatli-lezzet-hizmet-sozlesmesi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 2_097_152,
                'uploaded_at' => '2026-04-12',
                'uploaded_by' => 'Ahmet Yılmaz',
                'status' => 'active',
                'description' => null,
            ],
            [
                'id' => 13,
                'uuid' => 'doc-013',
                'business_id' => 6,
                'business_name' => 'Tatlı Diyarı Pastane ve Unlu Mamulleri',
                'name' => 'İmza Sirküsü',
                'document_type' => 'signature_circular',
                'file_name' => 'tatli-lezzet-imza-sirkuesi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 734_003,
                'uploaded_at' => '2026-04-12',
                'uploaded_by' => 'Ahmet Yılmaz',
                'status' => 'active',
                'description' => null,
            ],
            [
                'id' => 14,
                'uuid' => 'doc-014',
                'business_id' => 7,
                'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.',
                'name' => 'Faaliyet Belgesi',
                'document_type' => 'activity_certificate',
                'file_name' => 'anadolu-kebap-faaliyet-belgesi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 458_752,
                'uploaded_at' => '2025-09-30',
                'uploaded_by' => 'Mehmet Kaya',
                'status' => 'archived',
                'description' => 'Arşivlendi — yeni belge yüklendi.',
            ],
            [
                'id' => 15,
                'uuid' => 'doc-015',
                'business_id' => 7,
                'business_name' => 'Et ve Et Ürünleri Kasaplık Ltd. Şti.',
                'name' => 'Şube Açılış Fotoğrafları',
                'document_type' => 'other',
                'file_name' => 'anadolu-kebap-sube-acilis.png',
                'file_extension' => 'png',
                'file_size_bytes' => 3_670_016,
                'uploaded_at' => '2026-03-18',
                'uploaded_by' => 'Can Öztürk',
                'status' => 'active',
                'description' => 'Yeni şube açılış görselleri.',
            ],
            [
                'id' => 16,
                'uuid' => 'doc-016',
                'business_id' => 8,
                'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.',
                'name' => 'Ticaret Sicil Gazetesi',
                'document_type' => 'trade_registry',
                'file_name' => 'fresh-bowl-ticaret-sicil.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 1_310_720,
                'uploaded_at' => '2026-02-01',
                'uploaded_by' => 'Zeynep Arslan',
                'status' => 'active',
                'description' => null,
            ],
            [
                'id' => 17,
                'uuid' => 'doc-017',
                'business_id' => 8,
                'business_name' => 'Taze Manav ve Sebze Meyve Tic. Ltd. Şti.',
                'name' => 'Kurye Hizmet Sözleşmesi',
                'document_type' => 'contract',
                'file_name' => 'fresh-bowl-kurye-sozlesmesi.pdf',
                'file_extension' => 'pdf',
                'file_size_bytes' => 2_883_584,
                'uploaded_at' => '2026-02-15',
                'uploaded_by' => 'Mehmet Kaya',
                'status' => 'pending',
                'description' => 'Hukuk incelemesi bekleniyor.',
            ],
            [
                'id' => 18,
                'uuid' => 'doc-018',
                'business_id' => 1,
                'business_name' => 'Burger House Gıda Ltd. Şti.',
                'name' => 'Şube Listesi ve İletişim Bilgileri',
                'document_type' => 'other',
                'file_name' => 'burger-house-sube-listesi.xlsx',
                'file_extension' => 'xlsx',
                'file_size_bytes' => 184_320,
                'uploaded_at' => '2026-03-05',
                'uploaded_by' => 'Zeynep Arslan',
                'status' => 'active',
                'description' => 'Tüm şubelerin güncel iletişim listesi.',
            ],
            [
                'id' => 19,
                'uuid' => 'doc-019',
                'business_id' => 2,
                'business_name' => 'Napoli Pizza Restoran İşletmeleri A.Ş.',
                'name' => 'Evrak Arşivi 2025',
                'document_type' => 'other',
                'file_name' => 'napoli-pizza-evrak-arsivi-2025.zip',
                'file_extension' => 'zip',
                'file_size_bytes' => 28_311_552,
                'uploaded_at' => '2026-01-08',
                'uploaded_by' => 'Can Öztürk',
                'status' => 'archived',
                'description' => '2025 yılına ait tüm evrakların arşivi.',
            ],
            [
                'id' => 20,
                'uuid' => 'doc-020',
                'business_id' => 3,
                'business_name' => 'Yeşil Market Perakende Tic. Ltd. Şti.',
                'name' => 'Mağaza Cephe Fotoğrafı',
                'document_type' => 'other',
                'file_name' => 'yesil-market-magaza-cephe.jpg',
                'file_extension' => 'jpg',
                'file_size_bytes' => 2_621_440,
                'uploaded_at' => '2026-06-20',
                'uploaded_by' => 'Can Öztürk',
                'status' => 'active',
                'description' => 'Mağaza cephe görseli.',
            ],
        ];

        return collect($documents)
            ->map(fn (array $document) => self::enrich($document))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public static function enrich(array $document): array
    {
        $uploadedAt = Carbon::parse($document['uploaded_at']);

        return array_merge($document, [
            'document_type_label' => self::documentTypes()[$document['document_type']] ?? 'Diğer',
            'file_size_formatted' => self::formatFileSize($document['file_size_bytes']),
            'uploaded_at_formatted' => $uploadedAt->format('d.m.Y'),
            'file_type_label' => self::fileTypeLabel($document['file_extension']),
        ]);
    }

    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }

        return $bytes.' B';
    }

    public static function fileTypeLabel(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'PDF',
            'doc', 'docx' => 'Word',
            'xls', 'xlsx' => 'Excel',
            'jpg', 'jpeg', 'png', 'webp' => 'Resim',
            'zip' => 'ZIP',
            default => strtoupper($extension),
        };
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
            ->filter(function (array $document) use ($filters, $today) {
                if (! empty($filters['business_id']) && $filters['business_id'] !== 'all') {
                    if ((int) $document['business_id'] !== (int) $filters['business_id']) {
                        return false;
                    }
                }

                if (! empty($filters['document_type']) && $filters['document_type'] !== 'all') {
                    if ($document['document_type'] !== $filters['document_type']) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($document['status'] !== $filters['status']) {
                        return false;
                    }
                }

                if (! empty($filters['date_range']) && $filters['date_range'] !== 'all') {
                    $uploadedAt = Carbon::parse($document['uploaded_at']);

                    $matches = match ($filters['date_range']) {
                        'last_7_days' => $uploadedAt->gte($today->copy()->subDays(7)),
                        'last_30_days' => $uploadedAt->gte($today->copy()->subDays(30)),
                        'this_month' => $uploadedAt->isSameMonth($today),
                        'last_3_months' => $uploadedAt->gte($today->copy()->subMonths(3)),
                        'this_year' => $uploadedAt->year === $today->year,
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
