<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('earning_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('earning_lines', 'first_approved_by')) {
                $table->foreignId('first_approved_by')
                    ->nullable()
                    ->after('status_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('earning_lines', 'first_approved_at')) {
                $table->timestamp('first_approved_at')->nullable()->after('first_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('earning_lines', function (Blueprint $table) {
            if (Schema::hasColumn('earning_lines', 'first_approved_by')) {
                $table->dropConstrainedForeignId('first_approved_by');
            }

            if (Schema::hasColumn('earning_lines', 'first_approved_at')) {
                $table->dropColumn('first_approved_at');
            }
        });
    }
};
