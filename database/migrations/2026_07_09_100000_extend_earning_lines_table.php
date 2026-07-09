<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earning_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('earning_lines', 'pricing_model')) {
                $table->string('pricing_model')->default('per_package')->after('earning_type');
            }

            if (! Schema::hasColumn('earning_lines', 'extra_expense')) {
                $table->decimal('extra_expense', 14, 2)->default(0)->after('extra_payment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('earning_lines', function (Blueprint $table) {
            if (Schema::hasColumn('earning_lines', 'pricing_model')) {
                $table->dropColumn('pricing_model');
            }

            if (Schema::hasColumn('earning_lines', 'extra_expense')) {
                $table->dropColumn('extra_expense');
            }
        });
    }
};
