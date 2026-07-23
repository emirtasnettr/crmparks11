<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Soft-deactivate duplicate active earning payments before unique index.
        $duplicates = DB::table('finance_payments')
            ->select('earning_line_id', 'recipient_type', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
            ->whereNotNull('earning_line_id')
            ->where('is_active', true)
            ->groupBy('earning_line_id', 'recipient_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $cancelIds = DB::table('finance_payments')
                ->where('earning_line_id', $dup->earning_line_id)
                ->where('recipient_type', $dup->recipient_type)
                ->where('is_active', true)
                ->where('id', '!=', $dup->keep_id)
                ->pluck('id');

            if ($cancelIds->isEmpty()) {
                continue;
            }

            DB::table('finance_payments')
                ->whereIn('id', $cancelIds)
                ->update([
                    'is_active' => false,
                    'status' => 'cancelled',
                    'earning_line_id' => null,
                    'updated_at' => now(),
                ]);

            // İptal edilen ödemelerin cari earning yükümlülüklerini kaldır.
            DB::table('current_account_movements')
                ->where('type', 'earning')
                ->where('related_type', 'App\\Modules\\Finance\\Models\\FinancePayment')
                ->whereIn('related_id', $cancelIds)
                ->delete();
        }

        Schema::table('finance_payments', function (Blueprint $table) {
            $table->unique(
                ['earning_line_id', 'recipient_type'],
                'finance_payments_earning_recipient_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_payments', function (Blueprint $table) {
            $table->dropUnique('finance_payments_earning_recipient_unique');
        });
    }
};
