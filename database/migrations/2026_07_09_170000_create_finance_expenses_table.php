<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 30)->nullable()->unique();
            $table->string('expense_type', 30);
            $table->string('source', 20)->default('manual');
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            $table->foreignId('earning_line_id')->nullable()->constrained('earning_lines')->nullOnDelete();
            $table->foreignId('current_account_id')->nullable()->constrained('current_accounts')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->unsignedTinyInteger('vat_rate')->default(20);
            $table->date('expense_date');
            $table->string('payment_status', 20)->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('document_no', 50)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_type', 'fe_type_idx');
            $table->index('expense_date', 'fe_date_idx');
            $table->index('payment_status', 'fe_payment_status_idx');
            $table->index('courier_id', 'fe_courier_idx');
            $table->index('agency_id', 'fe_agency_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
    }
};
