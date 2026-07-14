<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'guaranteed_package_count')) {
                $table->decimal('guaranteed_package_count', 8, 2)->nullable()->after('planned_courier_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'guaranteed_package_count')) {
                $table->dropColumn('guaranteed_package_count');
            }
        });
    }
};
