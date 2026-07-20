<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('earning_lines') && Schema::hasColumn('earning_lines', 'assignment_id')) {
            Schema::table('earning_lines', function (Blueprint $table) {
                $table->dropConstrainedForeignId('assignment_id');
            });
        }

        Schema::dropIfExists('business_courier_assignments');
    }

    public function down(): void
    {
        if (! Schema::hasTable('business_courier_assignments')) {
            Schema::create('business_courier_assignments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
                $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
                $table->string('status', 30)->default('active');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['courier_id', 'status'], 'bca_courier_status_idx');
                $table->index(['business_id', 'status'], 'bca_business_status_idx');
            });
        }

        if (Schema::hasTable('earning_lines') && ! Schema::hasColumn('earning_lines', 'assignment_id')) {
            Schema::table('earning_lines', function (Blueprint $table) {
                $table->foreignId('assignment_id')
                    ->nullable()
                    ->after('courier_id')
                    ->constrained('business_courier_assignments')
                    ->nullOnDelete();
            });
        }
    }
};
