<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earning_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('earning_lines', 'work_date')) {
                $table->date('work_date')->nullable()->after('period_year');
                $table->index('work_date');
            }
        });

        if (Schema::hasColumn('earning_lines', 'work_date')) {
            DB::table('earning_lines')
                ->whereNull('work_date')
                ->whereNotNull('period_year')
                ->whereNotNull('period_month')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $year = (int) $row->period_year;
                        $month = (int) $row->period_month;

                        if ($year < 2000 || $month < 1 || $month > 12) {
                            continue;
                        }

                        DB::table('earning_lines')
                            ->where('id', $row->id)
                            ->update([
                                'work_date' => sprintf('%04d-%02d-01', $year, $month),
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('earning_lines', function (Blueprint $table): void {
            if (Schema::hasColumn('earning_lines', 'work_date')) {
                $table->dropIndex(['work_date']);
                $table->dropColumn('work_date');
            }
        });
    }
};
