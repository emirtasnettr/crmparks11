<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('earning_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('source')->default('manual');
            $table->string('source_file_path')->nullable();
            $table->unsignedInteger('total_lines')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('total_courier_payment', 14, 2)->default(0);
            $table->decimal('total_profit', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('earning_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('batch_id')->nullable()->constrained('earning_batches')->nullOnDelete();
            $table->foreignId('business_id')->constrained();
            $table->foreignId('courier_id')->constrained();
            $table->foreignId('assignment_id')->nullable()->constrained('business_courier_assignments')->nullOnDelete();
            $table->foreignId('business_pricing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('earning_type')->default('package_based');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedInteger('package_count')->default(0);
            $table->decimal('revenue_unit_price', 12, 2)->default(0);
            $table->decimal('revenue_total', 14, 2)->default(0);
            $table->decimal('courier_unit_price', 12, 2)->default(0);
            $table->decimal('courier_total', 14, 2)->default(0);
            $table->decimal('agency_payment', 14, 2)->default(0);
            $table->decimal('extra_payment', 14, 2)->default(0);
            $table->decimal('deduction', 14, 2)->default(0);
            $table->decimal('net_courier_payment', 14, 2)->default(0);
            $table->decimal('profit', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->foreignId('status_id')->constrained('earning_statuses');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['period_year', 'period_month', 'business_id']);
            $table->index(['courier_id', 'period_year', 'period_month']);
            $table->index('status_id');
        });

        Schema::create('period_locks', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->foreignId('locked_by')->constrained('users');
            $table->timestamp('locked_at');
            $table->unique(['period_month', 'period_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_locks');
        Schema::dropIfExists('earning_lines');
        Schema::dropIfExists('earning_batches');
    }
};
