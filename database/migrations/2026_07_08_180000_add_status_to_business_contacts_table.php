<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_contacts') && ! Schema::hasColumn('business_contacts', 'status')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                $table->string('status')->default('active')->after('is_default');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('business_contacts', 'status')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
