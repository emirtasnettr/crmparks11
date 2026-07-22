<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('current_account_movements', function (Blueprint $table) {
            $table->string('related_type', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('current_account_movements', function (Blueprint $table) {
            $table->string('related_type', 30)->nullable()->change();
        });
    }
};
