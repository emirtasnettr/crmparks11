<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_shift_attendances', function (Blueprint $table): void {
            $table->decimal('start_latitude', 10, 7)->nullable()->after('notes');
            $table->decimal('start_longitude', 10, 7)->nullable()->after('start_latitude');
            $table->unsignedSmallInteger('start_accuracy_meters')->nullable()->after('start_longitude');
            $table->unsignedInteger('start_distance_meters')->nullable()->after('start_accuracy_meters');
        });
    }

    public function down(): void
    {
        Schema::table('business_shift_attendances', function (Blueprint $table): void {
            $table->dropColumn([
                'start_latitude',
                'start_longitude',
                'start_accuracy_meters',
                'start_distance_meters',
            ]);
        });
    }
};
