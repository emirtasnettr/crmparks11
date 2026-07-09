<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agency_contacts')) {
            return;
        }

        if (! Schema::hasColumn('agency_contacts', 'status')) {
            Schema::table('agency_contacts', function (Blueprint $table): void {
                $table->string('status', 20)->default('active')->after('is_default');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agency_contacts') && Schema::hasColumn('agency_contacts', 'status')) {
            Schema::table('agency_contacts', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }
    }
};
