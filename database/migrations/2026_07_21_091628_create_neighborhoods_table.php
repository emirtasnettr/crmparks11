<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['district_id', 'name']);
            $table->index(['district_id', 'name']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('neighborhood_id')
                ->nullable()
                ->after('district_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('neighborhood_id');
        });

        Schema::dropIfExists('neighborhoods');
    }
};
