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
                $table->decimal('guaranteed_package_count', 8, 2)
                    ->nullable()
                    ->change();
            });
        }

        if (Schema::hasTable('business_shift_attendances')
            && Schema::hasColumn('business_shift_attendances', 'package_count')) {
            Schema::table('business_shift_attendances', function (Blueprint $table) {
                $table->decimal('package_count', 12, 2)
                    ->nullable()
                    ->change();
            });
        }

        if (Schema::hasTable('earning_lines')
            && Schema::hasColumn('earning_lines', 'package_count')) {
            Schema::table('earning_lines', function (Blueprint $table) {
                $table->decimal('package_count', 12, 2)
                    ->default(0)
                    ->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('earning_lines')
            && Schema::hasColumn('earning_lines', 'package_count')) {
            Schema::table('earning_lines', function (Blueprint $table) {
                $table->unsignedInteger('package_count')->default(0)->change();
            });
        }

        if (Schema::hasTable('business_shift_attendances')
            && Schema::hasColumn('business_shift_attendances', 'package_count')) {
            Schema::table('business_shift_attendances', function (Blueprint $table) {
                $table->unsignedInteger('package_count')->nullable()->change();
            });
        }

        if (Schema::hasTable('business_commercial_contracts')
            && Schema::hasColumn('business_commercial_contracts', 'guaranteed_package_count')) {
            Schema::table('business_commercial_contracts', function (Blueprint $table) {
                $table->unsignedInteger('guaranteed_package_count')->nullable()->change();
            });
        }
    }
};
