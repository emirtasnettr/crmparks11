<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_commercial_contracts')
            && ! Schema::hasColumn('business_commercial_contracts', 'guaranteed_package_count')) {
            Schema::table('business_commercial_contracts', function (Blueprint $table) {
                $table->unsignedInteger('guaranteed_package_count')
                    ->nullable()
                    ->after('guaranteed_hourly_package_fee');
            });
        }

        if (Schema::hasTable('business_shift_attendances')
            && ! Schema::hasColumn('business_shift_attendances', 'package_count')) {
            Schema::table('business_shift_attendances', function (Blueprint $table) {
                $table->unsignedInteger('package_count')
                    ->nullable()
                    ->after('worked_minutes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business_shift_attendances')
            && Schema::hasColumn('business_shift_attendances', 'package_count')) {
            Schema::table('business_shift_attendances', function (Blueprint $table) {
                $table->dropColumn('package_count');
            });
        }

        if (Schema::hasTable('business_commercial_contracts')
            && Schema::hasColumn('business_commercial_contracts', 'guaranteed_package_count')) {
            Schema::table('business_commercial_contracts', function (Blueprint $table) {
                $table->dropColumn('guaranteed_package_count');
            });
        }
    }
};
