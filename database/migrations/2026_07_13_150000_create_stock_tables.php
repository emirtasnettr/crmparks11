<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit')->default('adet');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('name');
        });

        Schema::create('stock_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('stock_product_id')->constrained('stock_products')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('assigned_at');
            $table->date('returned_at')->nullable();
            $table->string('status')->default('assigned');
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['stock_product_id', 'status']);
            $table->index(['courier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_assignments');
        Schema::dropIfExists('stock_products');
    }
};
