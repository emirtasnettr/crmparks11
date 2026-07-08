<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production-safe guard: adds Phase 2/3 columns only when missing.
 * Prevents 500s when code expects columns that migrations may not have applied yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agencies')) {
            Schema::table('agencies', function (Blueprint $table) {
                if (! Schema::hasColumn('agencies', 'brand_name')) {
                    $table->string('brand_name')->nullable()->after('company_name');
                }
                if (! Schema::hasColumn('agencies', 'website')) {
                    $table->string('website')->nullable()->after('email');
                }
                if (! Schema::hasColumn('agencies', 'authorized_person')) {
                    $table->string('authorized_person')->nullable()->after('district_id');
                }
                if (! Schema::hasColumn('agencies', 'logo_path')) {
                    $table->string('logo_path')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('agencies', 'mersis_number')) {
                    $table->string('mersis_number')->nullable()->after('tax_number');
                }
                if (! Schema::hasColumn('agencies', 'trade_registry_number')) {
                    $table->string('trade_registry_number')->nullable()->after('mersis_number');
                }
                if (! Schema::hasColumn('agencies', 'payment_period')) {
                    $table->string('payment_period')->nullable()->after('commission_rate');
                }
                if (! Schema::hasColumn('agencies', 'bank_key')) {
                    $table->string('bank_key')->nullable()->after('payment_period');
                }
                if (! Schema::hasColumn('agencies', 'account_holder')) {
                    $table->string('account_holder')->nullable()->after('bank_key');
                }
                if (! Schema::hasColumn('agencies', 'iban')) {
                    $table->string('iban')->nullable()->after('account_holder');
                }
            });
        }

        if (Schema::hasTable('couriers')) {
            Schema::table('couriers', function (Blueprint $table) {
                if (! Schema::hasColumn('couriers', 'email')) {
                    $table->string('email')->nullable()->after('phone');
                }
                if (! Schema::hasColumn('couriers', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('tc_number');
                }
                if (! Schema::hasColumn('couriers', 'city_id')) {
                    $table->foreignId('city_id')->nullable()->after('company_name')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('couriers', 'district_id')) {
                    $table->foreignId('district_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('couriers', 'address')) {
                    $table->text('address')->nullable()->after('district_id');
                }
                if (! Schema::hasColumn('couriers', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('couriers', 'tax_office')) {
                    $table->string('tax_office')->nullable()->after('tax_number');
                }
                if (! Schema::hasColumn('couriers', 'bank_name')) {
                    $table->string('bank_name')->nullable()->after('iban');
                }
                if (! Schema::hasColumn('couriers', 'account_holder')) {
                    $table->string('account_holder')->nullable()->after('bank_name');
                }
                if (! Schema::hasColumn('couriers', 'plate')) {
                    $table->string('plate')->nullable()->after('vehicle_type_id');
                }
                if (! Schema::hasColumn('couriers', 'vehicle_brand')) {
                    $table->string('vehicle_brand')->nullable()->after('plate');
                }
                if (! Schema::hasColumn('couriers', 'vehicle_model')) {
                    $table->string('vehicle_model')->nullable()->after('vehicle_brand');
                }
                if (! Schema::hasColumn('couriers', 'start_date')) {
                    $table->date('start_date')->nullable()->after('vehicle_model');
                }
            });
        }

        if (Schema::hasTable('business_contacts') && ! Schema::hasColumn('business_contacts', 'status')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                $table->string('status')->default('active')->after('is_default');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty — safety migration only.
    }
};
