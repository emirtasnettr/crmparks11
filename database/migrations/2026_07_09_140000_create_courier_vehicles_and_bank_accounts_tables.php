<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->string('vehicle_type');
            $table->string('plate')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->string('color')->nullable();
            $table->string('license_number')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->string('status')->default('active');
            $table->date('registered_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['courier_id', 'status'], 'cv_courier_status_idx');
            $table->index(['vehicle_type', 'status'], 'cv_type_status_idx');
        });

        Schema::create('courier_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->string('bank_key');
            $table->string('account_holder');
            $table->string('iban', 34);
            $table->string('branch_code')->nullable();
            $table->string('account_number')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['courier_id', 'is_default'], 'cba_courier_default_idx');
            $table->index(['courier_id', 'status'], 'cba_courier_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_bank_accounts');
        Schema::dropIfExists('courier_vehicles');
    }
};
