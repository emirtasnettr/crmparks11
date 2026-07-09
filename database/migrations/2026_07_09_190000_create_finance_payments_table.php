<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 30)->nullable()->unique();
            $table->string('recipient_type', 20);
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->foreignId('earning_line_id')->nullable()->constrained('earning_lines')->nullOnDelete();
            $table->foreignId('current_account_id')->nullable()->constrained('current_accounts')->nullOnDelete();
            $table->string('source', 20)->default('manual');
            $table->date('scheduled_date');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('recipient_type', 'fp_recipient_type_idx');
            $table->index('scheduled_date', 'fp_scheduled_date_idx');
            $table->index('status', 'fp_status_idx');
            $table->index('courier_id', 'fp_courier_idx');
            $table->index('agency_id', 'fp_agency_idx');
        });

        Schema::create('finance_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('finance_payments')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 50)->nullable();
            $table->string('bank_account', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('payment_id', 'fpl_payment_idx');
            $table->index('payment_date', 'fpl_payment_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payment_lines');
        Schema::dropIfExists('finance_payments');
    }
};
