<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $contractTypes = [
            ['code' => 'commission', 'label' => 'Komisyon Sözleşmesi', 'default_reminder_days' => 30],
            ['code' => 'courier_supply', 'label' => 'Kurye Tedarik Sözleşmesi', 'default_reminder_days' => 30],
        ];

        foreach ($contractTypes as $row) {
            DB::table('contract_types')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now]),
            );
        }

        $documentCategories = [
            ['code' => 'signature_circular', 'label' => 'İmza Sirküsü', 'allowed_mimes' => json_encode(['pdf', 'jpg', 'png'])],
            ['code' => 'activity_certificate', 'label' => 'Faaliyet Belgesi', 'allowed_mimes' => json_encode(['pdf'])],
            ['code' => 'trade_registry', 'label' => 'Ticaret Sicil Gazetesi', 'allowed_mimes' => json_encode(['pdf'])],
            ['code' => 'chamber_registration', 'label' => 'Oda Kayıt Belgesi', 'allowed_mimes' => json_encode(['pdf'])],
            ['code' => 'authorization_certificate', 'label' => 'Yetki Belgesi', 'allowed_mimes' => json_encode(['pdf'])],
            ['code' => 'sgk_clearance', 'label' => 'SGK Borcu Yoktur', 'allowed_mimes' => json_encode(['pdf'])],
            ['code' => 'tax_clearance', 'label' => 'Vergi Borcu Yoktur', 'allowed_mimes' => json_encode(['pdf'])],
        ];

        foreach ($documentCategories as $row) {
            DB::table('document_categories')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now]),
            );
        }
    }

    public function down(): void
    {
        DB::table('contract_types')->whereIn('code', ['commission', 'courier_supply'])->delete();
        DB::table('document_categories')->whereIn('code', [
            'signature_circular',
            'activity_certificate',
            'trade_registry',
            'chamber_registration',
            'authorization_certificate',
            'sgk_clearance',
            'tax_clearance',
        ])->delete();
    }
};
