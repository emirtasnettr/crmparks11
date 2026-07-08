<?php

namespace App\Modules\Courier\Data;

use App\Support\DemoData;

class CourierBankAccountDummyData
{
    /**
     * @return array<string, string>
     */
    public static function banks(): array
    {
        return CourierFormData::banks();
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Pasif',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultFilters(): array
    {
        return [
            'yes' => 'Varsayılan',
            'no' => 'Varsayılan Değil',
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public static function couriers(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(CourierDummyData::all())
            ->map(fn (array $courier) => [
                'id' => $courier['id'],
                'name' => $courier['full_name'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return collect(self::raw())
            ->map(fn (array $row) => self::enrich($row))
            ->sortByDesc(fn ($a) => sprintf('%d-%d-%03d', $a['is_default'] ? 1 : 0, $a['status'] === 'active' ? 1 : 0, $a['id']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function raw(): array
    {
        if (! DemoData::enabled()) {
            return [];
        }

return [
            ['id' => 1, 'courier_id' => 1, 'bank_key' => 'ziraat', 'account_holder' => 'Ahmet Yıldız', 'iban' => 'TR330006100519786457841326', 'branch_code' => '0610', 'account_number' => '51978645784', 'is_default' => true, 'status' => 'active', 'notes' => 'Ödemeler bu hesaba yapılır.'],
            ['id' => 2, 'courier_id' => 1, 'bank_key' => 'isbank', 'account_holder' => 'Ahmet Yıldız', 'iban' => 'TR640001000902863579985295', 'branch_code' => '1234', 'account_number' => '0286357998', 'is_default' => false, 'status' => 'inactive', 'notes' => 'Eski hesap — pasife alındı.'],
            ['id' => 3, 'courier_id' => 2, 'bank_key' => 'garanti', 'account_holder' => 'Murat Kaya', 'iban' => 'TR320006200519000006289951', 'branch_code' => '529', 'account_number' => '6298951', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 4, 'courier_id' => 2, 'bank_key' => 'akbank', 'account_holder' => 'Murat Kaya', 'iban' => 'TR460004600328888800001234', 'branch_code' => '328', 'account_number' => '888001234', 'is_default' => false, 'status' => 'inactive', 'notes' => 'Yedek hesap.'],
            ['id' => 5, 'courier_id' => 3, 'bank_key' => 'yapikredi', 'account_holder' => 'Emre Demir', 'iban' => 'TR670006701000000012345678', 'branch_code' => '455', 'account_number' => '12345678', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 6, 'courier_id' => 4, 'bank_key' => 'halkbank', 'account_holder' => 'Serkan Öz', 'iban' => 'TR120001200934500001001234', 'branch_code' => '934', 'account_number' => '50001001234', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 7, 'courier_id' => 4, 'bank_key' => 'vakifbank', 'account_holder' => 'Serkan Öz', 'iban' => 'TR150001500158007007123456', 'branch_code' => '1580', 'account_number' => '07007123456', 'is_default' => false, 'status' => 'inactive', 'notes' => null],
            ['id' => 8, 'courier_id' => 5, 'bank_key' => 'denizbank', 'account_holder' => 'Volkan Arslan', 'iban' => 'TR130001340001234567890123', 'branch_code' => '1340', 'account_number' => '12345678901', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 9, 'courier_id' => 6, 'bank_key' => 'qnb', 'account_holder' => 'Burak Şen', 'iban' => 'TR590011100000000001234567', 'branch_code' => '1110', 'account_number' => '1234567', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 10, 'courier_id' => 6, 'bank_key' => 'teb', 'account_holder' => 'Burak Şen', 'iban' => 'TR320003200000000012345678', 'branch_code' => '320', 'account_number' => '12345678', 'is_default' => false, 'status' => 'inactive', 'notes' => 'Eski TEB hesabı.'],
            ['id' => 11, 'courier_id' => 7, 'bank_key' => 'ziraat', 'account_holder' => 'Cem Akın', 'iban' => 'TR330006100519786457841327', 'branch_code' => '0610', 'account_number' => '51978645785', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 12, 'courier_id' => 8, 'bank_key' => 'isbank', 'account_holder' => 'Deniz Polat', 'iban' => 'TR640001000902863579985296', 'branch_code' => '1235', 'account_number' => '0286357999', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 13, 'courier_id' => 9, 'bank_key' => 'garanti', 'account_holder' => 'Efe Yalçın', 'iban' => 'TR320006200519000006289952', 'branch_code' => '530', 'account_number' => '6298952', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 14, 'courier_id' => 9, 'bank_key' => 'ziraat', 'account_holder' => 'Efe Yalçın', 'iban' => 'TR330006100519786457841328', 'branch_code' => '0611', 'account_number' => '51978645786', 'is_default' => false, 'status' => 'inactive', 'notes' => null],
            ['id' => 15, 'courier_id' => 10, 'bank_key' => 'akbank', 'account_holder' => 'Furkan Güneş', 'iban' => 'TR460004600328888800001235', 'branch_code' => '329', 'account_number' => '888001235', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 16, 'courier_id' => 11, 'bank_key' => 'yapikredi', 'account_holder' => 'Gökhan Tekin', 'iban' => 'TR670006701000000012345679', 'branch_code' => '456', 'account_number' => '12345679', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 17, 'courier_id' => 12, 'bank_key' => 'halkbank', 'account_holder' => 'Hakan Koç', 'iban' => 'TR120001200934500001001235', 'branch_code' => '935', 'account_number' => '50001001235', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 18, 'courier_id' => 12, 'bank_key' => 'garanti', 'account_holder' => 'Hakan Koç', 'iban' => 'TR320006200519000006289953', 'branch_code' => '531', 'account_number' => '6298953', 'is_default' => false, 'status' => 'inactive', 'notes' => 'İkinci hesap pasif.'],
            ['id' => 19, 'courier_id' => 13, 'bank_key' => 'vakifbank', 'account_holder' => 'İbrahim Çetin', 'iban' => 'TR150001500158007007123457', 'branch_code' => '1581', 'account_number' => '07007123457', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 20, 'courier_id' => 14, 'bank_key' => 'denizbank', 'account_holder' => 'Kaan Aydın', 'iban' => 'TR130001340001234567890124', 'branch_code' => '1341', 'account_number' => '12345678902', 'is_default' => false, 'status' => 'inactive', 'notes' => 'Pasif kurye hesabı.'],
            ['id' => 21, 'courier_id' => 15, 'bank_key' => 'qnb', 'account_holder' => 'Levent Sarı', 'iban' => 'TR590011100000000001234568', 'branch_code' => '1111', 'account_number' => '1234568', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 22, 'courier_id' => 16, 'bank_key' => 'teb', 'account_holder' => 'Mert Korkmaz', 'iban' => 'TR320003200000000012345679', 'branch_code' => '321', 'account_number' => '12345679', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 23, 'courier_id' => 17, 'bank_key' => 'ziraat', 'account_holder' => 'Oğuz Yılmaz', 'iban' => 'TR330006100519786457841329', 'branch_code' => '0612', 'account_number' => '51978645787', 'is_default' => false, 'status' => 'inactive', 'notes' => 'İzinli dönem.'],
            ['id' => 24, 'courier_id' => 18, 'bank_key' => 'isbank', 'account_holder' => 'Onur Başaran', 'iban' => 'TR640001000902863579985297', 'branch_code' => '1236', 'account_number' => '0286358000', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 25, 'courier_id' => 19, 'bank_key' => 'akbank', 'account_holder' => 'Rıza Öztürk', 'iban' => 'TR460004600328888800001236', 'branch_code' => '330', 'account_number' => '888001236', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 26, 'courier_id' => 20, 'bank_key' => 'yapikredi', 'account_holder' => 'Selim Erdoğan', 'iban' => 'TR670006701000000012345680', 'branch_code' => '457', 'account_number' => '12345680', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 27, 'courier_id' => 21, 'bank_key' => 'halkbank', 'account_holder' => 'Tolga Uçar', 'iban' => 'TR120001200934500001001236', 'branch_code' => '936', 'account_number' => '50001001236', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 28, 'courier_id' => 22, 'bank_key' => 'vakifbank', 'account_holder' => 'Umut Karaca', 'iban' => 'TR150001500158007007123458', 'branch_code' => '1582', 'account_number' => '07007123458', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 29, 'courier_id' => 23, 'bank_key' => 'denizbank', 'account_holder' => 'Yasin Duman', 'iban' => 'TR130001340001234567890125', 'branch_code' => '1342', 'account_number' => '12345678903', 'is_default' => false, 'status' => 'inactive', 'notes' => null],
            ['id' => 30, 'courier_id' => 24, 'bank_key' => 'qnb', 'account_holder' => 'Zafer Işık', 'iban' => 'TR590011100000000001234569', 'branch_code' => '1112', 'account_number' => '1234569', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 31, 'courier_id' => 24, 'bank_key' => 'teb', 'account_holder' => 'Zafer Işık', 'iban' => 'TR320003200000000012345680', 'branch_code' => '322', 'account_number' => '12345680', 'is_default' => false, 'status' => 'inactive', 'notes' => null],
            ['id' => 32, 'courier_id' => 25, 'bank_key' => 'ziraat', 'account_holder' => 'Barış Tunç', 'iban' => 'TR330006100519786457841330', 'branch_code' => '0613', 'account_number' => '51978645788', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 33, 'courier_id' => 26, 'bank_key' => 'isbank', 'account_holder' => 'Caner Bilgin', 'iban' => 'TR640001000902863579985298', 'branch_code' => '1237', 'account_number' => '0286358001', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 34, 'courier_id' => 27, 'bank_key' => 'garanti', 'account_holder' => 'Doğan Sezer', 'iban' => 'TR320006200519000006289954', 'branch_code' => '532', 'account_number' => '6298954', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 35, 'courier_id' => 28, 'bank_key' => 'akbank', 'account_holder' => 'Erhan Vural', 'iban' => 'TR460004600328888800001237', 'branch_code' => '331', 'account_number' => '888001237', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 36, 'courier_id' => 29, 'bank_key' => 'yapikredi', 'account_holder' => 'Fatih Gencer', 'iban' => 'TR670006701000000012345681', 'branch_code' => '458', 'account_number' => '12345681', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 37, 'courier_id' => 30, 'bank_key' => 'halkbank', 'account_holder' => 'Halil Özkan', 'iban' => 'TR120001200934500001001237', 'branch_code' => '937', 'account_number' => '50001001237', 'is_default' => false, 'status' => 'inactive', 'notes' => 'Pasif kurye.'],
            ['id' => 38, 'courier_id' => 31, 'bank_key' => 'vakifbank', 'account_holder' => 'İlker Mutlu', 'iban' => 'TR150001500158007007123459', 'branch_code' => '1583', 'account_number' => '07007123459', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 39, 'courier_id' => 32, 'bank_key' => 'denizbank', 'account_holder' => 'Koray Aslan', 'iban' => 'TR130001340001234567890126', 'branch_code' => '1343', 'account_number' => '12345678904', 'is_default' => true, 'status' => 'active', 'notes' => null],
            ['id' => 40, 'courier_id' => 3, 'bank_key' => 'ziraat', 'account_holder' => 'Emre Demir', 'iban' => 'TR330006100519786457841331', 'branch_code' => '0614', 'account_number' => '51978645789', 'is_default' => false, 'status' => 'inactive', 'notes' => 'Eski hesap.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        $courier = collect(CourierDummyData::all())->firstWhere('id', $row['courier_id']);

        return array_merge($row, [
            'uuid' => 'cbnk-'.str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT),
            'courier_name' => $courier['full_name'] ?? '—',
            'courier_phone' => $courier['phone'] ?? '—',
            'courier_type' => $courier['courier_type'] ?? 'independent',
            'bank_name' => self::banks()[$row['bank_key']] ?? '—',
            'iban_masked' => self::maskIban($row['iban']),
            'iban_formatted' => self::formatIban($row['iban']),
            'status_label' => self::statuses()[$row['status']] ?? '—',
        ]);
    }

    public static function maskIban(string $iban): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $iban) ?? $iban);

        if (strlen($clean) < 8) {
            return $clean;
        }

        $first = substr($clean, 0, 4);
        $last = substr($clean, -4);

        return $first.' **** **** **** **** '.$last;
    }

    public static function formatIban(string $iban): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $iban) ?? $iban);

        return trim(chunk_split($clean, 4, ' '));
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $account) {
            if ($account['id'] === $id) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function courierAccounts(int $courierId): array
    {
        return collect(self::all())
            ->where('courier_id', $courierId)
            ->sortByDesc(fn ($a) => sprintf('%d-%03d', $a['is_default'] ? 1 : 0, $a['id']))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, int>
     */
    public static function summarize(array $items): array
    {
        return [
            'count' => count($items),
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
            ->filter(function (array $account) use ($filters) {
                if (! empty($filters['courier_id']) && $filters['courier_id'] !== 'all') {
                    if ((int) $account['courier_id'] !== (int) $filters['courier_id']) {
                        return false;
                    }
                }

                if (! empty($filters['search'])) {
                    $search = mb_strtolower($filters['search']);
                    $haystack = mb_strtolower(implode(' ', [
                        $account['courier_name'],
                        $account['account_holder'],
                        $account['iban'],
                    ]));

                    if (! str_contains($haystack, $search)) {
                        return false;
                    }
                }

                if (! empty($filters['bank_key']) && $filters['bank_key'] !== 'all') {
                    if ($account['bank_key'] !== $filters['bank_key']) {
                        return false;
                    }
                }

                if (! empty($filters['is_default']) && $filters['is_default'] !== 'all') {
                    $isDefault = $filters['is_default'] === 'yes';

                    if ((bool) $account['is_default'] !== $isDefault) {
                        return false;
                    }
                }

                if (! empty($filters['status']) && $filters['status'] !== 'all') {
                    if ($account['status'] !== $filters['status']) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc(fn ($a) => sprintf('%d-%d-%03d', $a['is_default'] ? 1 : 0, $a['status'] === 'active' ? 1 : 0, $a['id']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{courier_id: int, default_count: int}>
     */
    public static function defaultAccountViolations(): array
    {
        return collect(self::all())
            ->where('is_default', true)
            ->groupBy('courier_id')
            ->map(fn ($group, $courierId) => [
                'courier_id' => (int) $courierId,
                'default_count' => $group->count(),
            ])
            ->where('default_count', '>', 1)
            ->values()
            ->all();
    }
}
