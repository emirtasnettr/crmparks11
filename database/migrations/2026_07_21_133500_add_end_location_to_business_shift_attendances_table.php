<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_shift_attendances', function (Blueprint $table): void {
            $table->decimal('end_latitude', 10, 7)->nullable()->after('start_distance_meters');
            $table->decimal('end_longitude', 10, 7)->nullable()->after('end_latitude');
            $table->unsignedSmallInteger('end_accuracy_meters')->nullable()->after('end_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('business_shift_attendances', function (Blueprint $table): void {
            $table->dropColumn([
                'end_latitude',
                'end_longitude',
                'end_accuracy_meters',
            ]);
        });
    }
};
