<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference', 30)->nullable()->unique();
            $table->foreignId('business_id')->constrained('businesses');
            $table->foreignId('earning_line_id')->nullable()->constrained('earning_lines')->nullOnDelete();
            $table->foreignId('current_account_id')->nullable()->constrained('current_accounts')->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained('finance_collections')->nullOnDelete();
            $table->string('invoice_type', 20)->default('manual');
            $table->string('invoice_status', 20)->default('issued');
            $table->string('collection_status', 20)->default('pending');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('subtotal', 14, 2);
            $table->unsignedTinyInteger('vat_rate')->default(20);
            $table->decimal('vat_amount', 14, 2);
            $table->decimal('grand_total', 14, 2);
            $table->decimal('collected_amount', 14, 2)->default(0);
            $table->string('source', 20)->default('manual');
            $table->string('e_invoice_uuid', 80)->nullable();
            $table->string('e_archive_uuid', 80)->nullable();
            $table->string('gib_status', 20)->default('not_applicable');
            $table->string('pdf_filename', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('earning_line_id', 'fi_earning_line_uq');
            $table->index('business_id', 'fi_business_idx');
            $table->index('invoice_date', 'fi_invoice_date_idx');
            $table->index('invoice_status', 'fi_invoice_status_idx');
            $table->index('collection_status', 'fi_collection_status_idx');
            $table->index('invoice_type', 'fi_invoice_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_invoices');
    }
};
