<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignId('business_id')->constrained('businesses');
            $table->foreignId('revenue_id')->nullable()->constrained('finance_revenues')->nullOnDelete();
            $table->foreignId('current_account_id')->nullable()->constrained('current_accounts')->nullOnDelete();
            $table->string('source', 20)->default('manual');
            $table->string('invoice_no', 50)->nullable();
            $table->date('due_date');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('collected_amount', 14, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('business_id', 'fc_business_idx');
            $table->index('due_date', 'fc_due_date_idx');
            $table->index('status', 'fc_status_idx');
            $table->index('revenue_id', 'fc_revenue_idx');
        });

        Schema::create('finance_collection_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('finance_collections')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_reference', 50)->nullable();
            $table->string('bank', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('collection_id', 'fcp_collection_idx');
            $table->index('payment_date', 'fcp_payment_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_collection_payments');
        Schema::dropIfExists('finance_collections');
    }
};
