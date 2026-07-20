<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_commercial_contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('work_type', 30); // per_package | hourly
            $table->decimal('business_amount', 12, 2); // İşletmeden alınan (KDV hariç)
            $table->decimal('courier_amount', 12, 2); // Kuryeye verilen (KDV hariç)
            $table->decimal('net_profit', 12, 2); // Net kazanç
            $table->decimal('guaranteed_hourly_package_fee', 12, 2)->nullable(); // Paket başı için opsiyonel
            $table->string('payment_period', 20); // weekly | biweekly | monthly
            $table->string('status', 20)->default('active'); // active | ended
            $table->foreignId('supersedes_id')->nullable()->constrained('business_commercial_contracts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'start_date', 'end_date']);
        });

        if (Schema::hasTable('business_shift_attendances')) {
            Schema::table('business_shift_attendances', function (Blueprint $table) {
                $table->foreignId('commercial_contract_id')
                    ->nullable()
                    ->after('business_id')
                    ->constrained('business_commercial_contracts')
                    ->nullOnDelete();
            });
        }

        $this->backfillFromPricing();
    }

    public function down(): void
    {
        if (Schema::hasTable('business_shift_attendances') && Schema::hasColumn('business_shift_attendances', 'commercial_contract_id')) {
            Schema::table('business_shift_attendances', function (Blueprint $table) {
                $table->dropConstrainedForeignId('commercial_contract_id');
            });
        }

        Schema::dropIfExists('business_commercial_contracts');
    }

    private function backfillFromPricing(): void
    {
        if (! Schema::hasTable('business_pricings') || ! Schema::hasTable('businesses')) {
            return;
        }

        $now = now();
        $rows = DB::table('business_pricings as bp')
            ->leftJoin('pricing_model_types as pmt', 'pmt.id', '=', 'bp.pricing_model_type_id')
            ->leftJoin('businesses as b', 'b.id', '=', 'bp.business_id')
            ->whereNull('bp.deleted_at')
            ->where('bp.is_active', true)
            ->select([
                'bp.business_id',
                'bp.customer_unit_price',
                'bp.courier_unit_price',
                'bp.effective_from',
                'pmt.code as pricing_code',
                'b.earning_period',
                'b.guaranteed_package_count',
            ])
            ->get();

        foreach ($rows as $row) {
            $workType = in_array($row->pricing_code, ['hourly'], true) ? 'hourly' : 'per_package';
            $businessAmount = round((float) $row->customer_unit_price, 2);
            $courierAmount = round((float) $row->courier_unit_price, 2);
            $period = in_array($row->earning_period, ['weekly', 'biweekly', 'monthly'], true)
                ? $row->earning_period
                : 'monthly';

            DB::table('business_commercial_contracts')->insert([
                'uuid' => (string) Str::uuid(),
                'business_id' => $row->business_id,
                'start_date' => $row->effective_from ?? $now->toDateString(),
                'end_date' => null,
                'work_type' => $workType,
                'business_amount' => $businessAmount,
                'courier_amount' => $courierAmount,
                'net_profit' => round($businessAmount - $courierAmount, 2),
                'guaranteed_hourly_package_fee' => null,
                'payment_period' => $period,
                'status' => 'active',
                'supersedes_id' => null,
                'notes' => 'Mevcut fiyatlandırmadan otomatik aktarıldı.',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
