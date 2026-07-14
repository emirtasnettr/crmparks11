<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('business_shifts', 'required_headcount')) {
            Schema::table('business_shifts', function (Blueprint $table) {
                $table->unsignedInteger('required_headcount')->default(1)->after('end_time');
            });
        }

        if (! Schema::hasTable('business_shift_couriers')) {
            Schema::create('business_shift_couriers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_shift_id')->constrained('business_shifts')->cascadeOnDelete();
                $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['business_shift_id', 'courier_id'], 'bsc_shift_courier_unique');
                $table->index('courier_id', 'bsc_courier_idx');
            });
        }

        if (! Schema::hasTable('business_shift_joker_assignments')) {
            Schema::create('business_shift_joker_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_shift_id')->constrained('business_shifts')->cascadeOnDelete();
                $table->date('work_date');
                $table->foreignId('absent_courier_id')->constrained('couriers')->cascadeOnDelete();
                $table->foreignId('joker_courier_id')->constrained('couriers')->cascadeOnDelete();
                $table->string('reason')->default('izin');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['business_shift_id', 'work_date', 'absent_courier_id'], 'shift_joker_unique');
                $table->index(['business_shift_id', 'work_date'], 'shift_joker_date_idx');
            });
        } else {
            $this->ensureJokerDateIndex();
        }

        // Mevcut gün bazlı atamalardan kalıcı kadroya taşı (her vardiyada benzersiz kuryeler).
        if (Schema::hasTable('business_shift_day_couriers') && Schema::hasTable('business_shift_couriers')) {
            $rows = DB::table('business_shift_day_couriers')
                ->select('business_shift_id', 'courier_id')
                ->distinct()
                ->get();

            $now = now();
            foreach ($rows as $row) {
                DB::table('business_shift_couriers')->insertOrIgnore([
                    'business_shift_id' => $row->business_shift_id,
                    'courier_id' => $row->courier_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // required_headcount = kadro sayısı (en az 1)
            $counts = DB::table('business_shift_couriers')
                ->select('business_shift_id', DB::raw('COUNT(*) as total'))
                ->groupBy('business_shift_id')
                ->get();

            foreach ($counts as $count) {
                DB::table('business_shifts')
                    ->where('id', $count->business_shift_id)
                    ->update(['required_headcount' => max(1, (int) $count->total)]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_shift_joker_assignments');
        Schema::dropIfExists('business_shift_couriers');

        if (Schema::hasColumn('business_shifts', 'required_headcount')) {
            Schema::table('business_shifts', function (Blueprint $table) {
                $table->dropColumn('required_headcount');
            });
        }
    }

    private function ensureJokerDateIndex(): void
    {
        $indexes = collect(Schema::getIndexes('business_shift_joker_assignments'));
        $hasDateIndex = $indexes->contains(
            fn (array $index): bool => ($index['columns'] ?? []) === ['business_shift_id', 'work_date']
        );

        if ($hasDateIndex) {
            return;
        }

        Schema::table('business_shift_joker_assignments', function (Blueprint $table) {
            $table->index(['business_shift_id', 'work_date'], 'shift_joker_date_idx');
        });
    }
};
