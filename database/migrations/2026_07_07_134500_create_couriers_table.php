<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->string('phone');
            $table->string('tc_number')->unique();
            $table->string('tax_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('iban')->nullable();
            $table->foreignId('vehicle_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('courier_type')->default('independent');
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('courier_type');
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
