<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'is_active'], 'bs_business_active_idx');
        });

        Schema::create('business_shift_courier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_shift_id')->constrained('business_shifts')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_shift_id', 'courier_id'], 'bsc_shift_courier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_shift_courier');
        Schema::dropIfExists('business_shifts');
    }
};
