<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('current_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->string('account_type', 20);
            $table->nullableMorphs('accountable', 'ca_accountable_idx');
            $table->string('title');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_number', 20)->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_type', 'status'], 'ca_type_status_idx');
        });

        Schema::create('current_account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_account_id')->constrained('current_accounts')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('document_no', 50)->nullable();
            $table->string('type', 30);
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('related_type', 255)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['current_account_id', 'transaction_date'], 'cam_account_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('current_account_movements');
        Schema::dropIfExists('current_accounts');
    }
};
