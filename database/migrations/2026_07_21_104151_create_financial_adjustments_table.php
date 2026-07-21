<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 20);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('current_account_id')->nullable()->constrained('current_accounts')->nullOnDelete();
            $table->foreignId('earning_line_id')->nullable()->constrained('earning_lines')->nullOnDelete();
            $table->string('direction', 10);
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_account_movement_id')
                ->nullable()
                ->constrained('current_account_movements')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('earning_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_adjustments');
    }
};
