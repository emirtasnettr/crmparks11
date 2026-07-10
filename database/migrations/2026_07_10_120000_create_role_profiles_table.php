<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_profiles');
    }
};
