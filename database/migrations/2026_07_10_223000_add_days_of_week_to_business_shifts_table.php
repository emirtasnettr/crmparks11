<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_shifts', function (Blueprint $table) {
            $table->json('days_of_week')->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('business_shifts', function (Blueprint $table) {
            $table->dropColumn('days_of_week');
        });
    }
};
