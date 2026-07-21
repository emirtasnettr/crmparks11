<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_shift_attendances', function (Blueprint $table) {
            $table->string('end_reason', 40)->nullable()->after('notes');
            $table->foreignId('replaces_attendance_id')
                ->nullable()
                ->after('end_reason')
                ->constrained('business_shift_attendances')
                ->nullOnDelete();
            $table->foreignId('replaced_by_attendance_id')
                ->nullable()
                ->after('replaces_attendance_id')
                ->constrained('business_shift_attendances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('business_shift_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaced_by_attendance_id');
            $table->dropConstrainedForeignId('replaces_attendance_id');
            $table->dropColumn('end_reason');
        });
    }
};
