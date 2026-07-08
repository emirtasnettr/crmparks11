<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('company_name');
            $table->string('website')->nullable()->after('email');
            $table->string('authorized_person')->nullable()->after('district_id');
            $table->string('logo_path')->nullable()->after('notes');
            $table->string('mersis_number')->nullable()->after('tax_number');
            $table->string('trade_registry_number')->nullable()->after('mersis_number');
            $table->string('payment_period')->nullable()->after('commission_rate');
            $table->string('bank_key')->nullable()->after('payment_period');
            $table->string('account_holder')->nullable()->after('bank_key');
            $table->string('iban')->nullable()->after('account_holder');
        });

        Schema::table('couriers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('tc_number');
            $table->foreignId('city_id')->nullable()->after('company_name')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
            $table->text('address')->nullable()->after('district_id');
            $table->string('photo_path')->nullable()->after('notes');
            $table->string('tax_office')->nullable()->after('tax_number');
            $table->string('bank_name')->nullable()->after('iban');
            $table->string('account_holder')->nullable()->after('bank_name');
            $table->string('plate')->nullable()->after('vehicle_type_id');
            $table->string('vehicle_brand')->nullable()->after('plate');
            $table->string('vehicle_model')->nullable()->after('vehicle_brand');
            $table->date('start_date')->nullable()->after('vehicle_model');
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn([
                'email', 'birth_date', 'address', 'photo_path', 'tax_office',
                'bank_name', 'account_holder', 'plate', 'vehicle_brand', 'vehicle_model', 'start_date',
            ]);
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'brand_name', 'website', 'authorized_person', 'logo_path',
                'mersis_number', 'trade_registry_number', 'payment_period',
                'bank_key', 'account_holder', 'iban',
            ]);
        });
    }
};
