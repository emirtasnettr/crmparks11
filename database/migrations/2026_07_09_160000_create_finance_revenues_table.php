<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_revenues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignId('business_id')->constrained('businesses');
            $table->foreignId('earning_line_id')->nullable()->constrained('earning_lines')->nullOnDelete();
            $table->foreignId('current_account_id')->nullable()->constrained('current_accounts')->nullOnDelete();
            $table->string('revenue_type', 30);
            $table->unsignedTinyInteger('period_month')->nullable();
            $table->unsignedSmallInteger('period_year')->nullable();
            $table->string('period_label')->nullable();
            $table->string('invoice_no', 50)->nullable();
            $table->string('invoice_status', 20)->default('none');
            $table->decimal('amount', 14, 2);
            $table->unsignedTinyInteger('vat_rate')->default(20);
            $table->string('collection_status', 20)->default('pending');
            $table->date('collection_date')->nullable();
            $table->date('revenue_date');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('business_id', 'fr_business_idx');
            $table->index('revenue_date', 'fr_revenue_date_idx');
            $table->index('collection_status', 'fr_collection_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_revenues');
    }
};
