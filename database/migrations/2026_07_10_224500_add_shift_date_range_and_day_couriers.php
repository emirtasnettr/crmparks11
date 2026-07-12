<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_shifts', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('end_time');
            $table->date('end_date')->nullable()->after('start_date');
        });

        Schema::create('business_shift_day_couriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_shift_id')->constrained('business_shifts')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_shift_id', 'work_date', 'courier_id'], 'bsdc_shift_date_courier_unique');
            $table->index(['business_shift_id', 'work_date'], 'bsdc_shift_date_idx');
        });

        if (Schema::hasTable('business_shift_courier')) {
            $rows = DB::table('business_shift_courier')->get();
            $now = now();

            foreach ($rows as $row) {
                $shift = DB::table('business_shifts')->where('id', $row->business_shift_id)->first();
                if ($shift === null) {
                    continue;
                }

                $start = $shift->start_date
                    ? \Carbon\Carbon::parse($shift->start_date)
                    : now()->startOfWeek(\Carbon\Carbon::MONDAY);
                $end = $shift->end_date
                    ? \Carbon\Carbon::parse($shift->end_date)
                    : $start->copy()->addWeeks(4)->endOfWeek(\Carbon\Carbon::SUNDAY);

                $days = json_decode($shift->days_of_week ?? '[]', true);
                if (! is_array($days) || $days === []) {
                    $days = [1, 2, 3, 4, 5, 6, 7];
                }
                $days = array_map('intval', $days);

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    if (! in_array((int) $date->dayOfWeekIso, $days, true)) {
                        continue;
                    }

                    DB::table('business_shift_day_couriers')->updateOrInsert(
                        [
                            'business_shift_id' => $row->business_shift_id,
                            'work_date' => $date->toDateString(),
                            'courier_id' => $row->courier_id,
                        ],
                        [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            }

            Schema::drop('business_shift_courier');
        }

        $fallbackStart = now()->toDateString();
        $fallbackEnd = now()->addWeeks(4)->toDateString();

        DB::table('business_shifts')
            ->whereNull('start_date')
            ->update([
                'start_date' => $fallbackStart,
                'end_date' => $fallbackEnd,
            ]);
    }

    public function down(): void
    {
        Schema::create('business_shift_courier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_shift_id')->constrained('business_shifts')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['business_shift_id', 'courier_id'], 'bsc_shift_courier_unique');
        });

        Schema::dropIfExists('business_shift_day_couriers');

        Schema::table('business_shifts', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
