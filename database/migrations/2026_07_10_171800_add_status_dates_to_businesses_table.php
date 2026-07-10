<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            if (! Schema::hasColumn('businesses', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('businesses', 'estimated_opening_date')) {
                $table->date('estimated_opening_date')->nullable()->after('contract_end_date');
            }

            if (! Schema::hasColumn('businesses', 'start_date')) {
                $table->date('start_date')->nullable()->after('estimated_opening_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('businesses', 'contract_end_date') ? 'contract_end_date' : null,
                Schema::hasColumn('businesses', 'estimated_opening_date') ? 'estimated_opening_date' : null,
                Schema::hasColumn('businesses', 'start_date') ? 'start_date' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
