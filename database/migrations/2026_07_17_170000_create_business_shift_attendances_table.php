<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_shift_attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_shift_id')->constrained('business_shifts')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status')->default('in_progress'); // in_progress|completed|cancelled
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->decimal('earnings_amount', 12, 2)->nullable();
            $table->string('pricing_model')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['courier_id', 'work_date']);
            $table->index(['business_id', 'work_date']);
            $table->index(['business_shift_id', 'work_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_shift_attendances');
    }
};
