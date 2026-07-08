<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('vehicle_types')->insert([
            ['code' => 'motor', 'label' => 'Motor', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'car', 'label' => 'Otomobil', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'bicycle', 'label' => 'Bisiklet', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pedestrian', 'label' => 'Yaya', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('contract_types')->insert([
            ['code' => 'service', 'label' => 'Hizmet Sözleşmesi', 'default_reminder_days' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'courier', 'label' => 'Kurye Sözleşmesi', 'default_reminder_days' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'agency', 'label' => 'Acente Sözleşmesi', 'default_reminder_days' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'framework', 'label' => 'Çerçeve Sözleşme', 'default_reminder_days' => 60, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('pricing_model_types')->insert([
            ['code' => 'per_package', 'label' => 'Paket Başı', 'requires_package_count' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hourly', 'label' => 'Saatlik', 'requires_package_count' => false, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'daily', 'label' => 'Günlük', 'requires_package_count' => false, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'monthly_fixed', 'label' => 'Aylık Sabit', 'requires_package_count' => false, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'weekly_fixed', 'label' => 'Haftalık Sabit', 'requires_package_count' => false, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'custom', 'label' => 'Özel Fiyatlandırma', 'requires_package_count' => false, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('document_categories')->insert([
            ['code' => 'contract', 'label' => 'Sözleşme', 'allowed_mimes' => json_encode(['pdf']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'tax_plate', 'label' => 'Vergi Levhası', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'identity', 'label' => 'Kimlik', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'license', 'label' => 'Ehliyet', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'registration', 'label' => 'Ruhsat', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'residence', 'label' => 'İkametgah', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'logo', 'label' => 'Logo', 'allowed_mimes' => json_encode(['png', 'jpg', 'jpeg']), 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'other', 'label' => 'Diğer', 'allowed_mimes' => json_encode(['pdf', 'xlsx', 'doc', 'docx', 'png', 'jpg', 'zip']), 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('earning_statuses')->insert([
            ['code' => 'draft', 'label' => 'Taslak', 'color' => 'secondary', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pending_review', 'label' => 'Onay Bekliyor', 'color' => 'warning', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'approved', 'label' => 'Onaylandı', 'color' => 'success', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'paid', 'label' => 'Ödendi', 'color' => 'primary', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'cancelled', 'label' => 'İptal', 'color' => 'danger', 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
