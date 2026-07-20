<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earning_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('earning_lines', 'worked_hours')) {
                $table->decimal('worked_hours', 10, 2)->default(0)->after('package_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('earning_lines', function (Blueprint $table) {
            if (Schema::hasColumn('earning_lines', 'worked_hours')) {
                $table->dropColumn('worked_hours');
            }
        });
    }
};
