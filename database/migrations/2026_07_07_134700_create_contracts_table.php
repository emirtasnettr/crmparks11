<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('contractable');
            $table->foreignId('contract_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('contract_number')->nullable()->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('auto_reminder')->default(true);
            $table->unsignedSmallInteger('reminder_days_before')->default(30);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
