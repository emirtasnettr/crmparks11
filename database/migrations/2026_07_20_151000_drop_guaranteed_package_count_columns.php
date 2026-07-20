<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_commercial_contracts')
            && Schema::hasColumn('business_commercial_contracts', 'guaranteed_package_count')) {
            Schema::table('business_commercial_contracts', function (Blueprint $table) {
                $table->dropColumn('guaranteed_package_count');
            });
        }

        if (Schema::hasTable('businesses')
            && Schema::hasColumn('businesses', 'guaranteed_package_count')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('guaranteed_package_count');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business_commercial_contracts')
            && ! Schema::hasColumn('business_commercial_contracts', 'guaranteed_package_count')) {
            Schema::table('business_commercial_contracts', function (Blueprint $table) {
                $table->decimal('guaranteed_package_count', 8, 2)
                    ->nullable()
                    ->after('guaranteed_hourly_package_fee');
            });
        }

        if (Schema::hasTable('businesses')
            && ! Schema::hasColumn('businesses', 'guaranteed_package_count')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->decimal('guaranteed_package_count', 8, 2)
                    ->nullable()
                    ->after('planned_courier_count');
            });
        }
    }
};
