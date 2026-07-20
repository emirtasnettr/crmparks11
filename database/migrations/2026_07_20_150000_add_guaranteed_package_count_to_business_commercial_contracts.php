<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_commercial_contracts', function (Blueprint $table) {
            $table->decimal('guaranteed_package_count', 8, 2)
                ->nullable()
                ->after('guaranteed_hourly_package_fee');
        });

        if (Schema::hasColumn('businesses', 'guaranteed_package_count')) {
            $rows = DB::table('businesses')
                ->whereNotNull('guaranteed_package_count')
                ->where('guaranteed_package_count', '>', 0)
                ->get(['id', 'guaranteed_package_count']);

            foreach ($rows as $row) {
                DB::table('business_commercial_contracts')
                    ->where('business_id', $row->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->update([
                        'guaranteed_package_count' => $row->guaranteed_package_count,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('business_commercial_contracts', function (Blueprint $table) {
            $table->dropColumn('guaranteed_package_count');
        });
    }
};
