<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->upsertRows('vehicle_types', [
            ['code' => 'motor', 'label' => 'Motor', 'sort_order' => 1],
            ['code' => 'car', 'label' => 'Otomobil', 'sort_order' => 2],
            ['code' => 'bicycle', 'label' => 'Bisiklet', 'sort_order' => 3],
            ['code' => 'pedestrian', 'label' => 'Yaya', 'sort_order' => 4],
        ], ['code'], ['label', 'sort_order', 'updated_at'], $now);

        $this->upsertRows('contract_types', [
            ['code' => 'service', 'label' => 'Hizmet Sözleşmesi', 'default_reminder_days' => 30],
            ['code' => 'courier', 'label' => 'Kurye Sözleşmesi', 'default_reminder_days' => 30],
            ['code' => 'agency', 'label' => 'Acente Sözleşmesi', 'default_reminder_days' => 30],
            ['code' => 'framework', 'label' => 'Çerçeve Sözleşme', 'default_reminder_days' => 60],
        ], ['code'], ['label', 'default_reminder_days', 'updated_at'], $now);

        $this->upsertRows('pricing_model_types', [
            ['code' => 'per_package', 'label' => 'Paket Başı', 'requires_package_count' => true, 'sort_order' => 1],
            ['code' => 'hourly', 'label' => 'Saatlik', 'requires_package_count' => false, 'sort_order' => 2],
            ['code' => 'daily', 'label' => 'Günlük', 'requires_package_count' => false, 'sort_order' => 3],
            ['code' => 'monthly_fixed', 'label' => 'Aylık Sabit', 'requires_package_count' => false, 'sort_order' => 4],
            ['code' => 'weekly_fixed', 'label' => 'Haftalık Sabit', 'requires_package_count' => false, 'sort_order' => 5],
            ['code' => 'custom', 'label' => 'Özel Fiyatlandırma', 'requires_package_count' => false, 'sort_order' => 6],
        ], ['code'], ['label', 'requires_package_count', 'sort_order', 'updated_at'], $now);

        $this->upsertRows('document_categories', [
            ['code' => 'contract', 'label' => 'Sözleşme', 'allowed_mimes' => json_encode(['pdf'])],
            ['code' => 'tax_plate', 'label' => 'Vergi Levhası', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png'])],
            ['code' => 'identity', 'label' => 'Kimlik', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png'])],
            ['code' => 'license', 'label' => 'Ehliyet', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png'])],
            ['code' => 'registration', 'label' => 'Ruhsat', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png'])],
            ['code' => 'residence', 'label' => 'İkametgah', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png'])],
            ['code' => 'logo', 'label' => 'Logo', 'allowed_mimes' => json_encode(['png', 'jpg', 'jpeg'])],
            ['code' => 'other', 'label' => 'Diğer', 'allowed_mimes' => json_encode(['pdf', 'xlsx', 'doc', 'docx', 'png', 'jpg', 'zip'])],
        ], ['code'], ['label', 'allowed_mimes', 'updated_at'], $now);

        $this->upsertRows('earning_statuses', [
            ['code' => 'draft', 'label' => 'Taslak', 'color' => 'secondary', 'sort_order' => 1],
            ['code' => 'pending_review', 'label' => 'Onay Bekliyor', 'color' => 'warning', 'sort_order' => 2],
            ['code' => 'approved', 'label' => 'Onaylandı', 'color' => 'success', 'sort_order' => 3],
            ['code' => 'paid', 'label' => 'Ödendi', 'color' => 'primary', 'sort_order' => 4],
            ['code' => 'cancelled', 'label' => 'İptal', 'color' => 'danger', 'sort_order' => 5],
        ], ['code'], ['label', 'color', 'sort_order', 'updated_at'], $now);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $updateColumns
     */
    private function upsertRows(string $table, array $rows, array $uniqueBy, array $updateColumns, \DateTimeInterface $now): void
    {
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::table($table)->upsert($rows, $uniqueBy, $updateColumns);
    }
}
