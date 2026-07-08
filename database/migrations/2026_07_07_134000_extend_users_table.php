<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('user_type')->default('internal')->after('avatar_path');
            $table->nullableMorphs('profileable');
            $table->string('status')->default('active')->after('profileable_type');
            $table->string('theme')->default('system')->after('status');
            $table->string('locale')->default('tr')->after('theme');
            $table->timestamp('last_login_at')->nullable()->after('locale');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'uuid', 'phone', 'avatar_path', 'user_type',
                'profileable_type', 'profileable_id',
                'status', 'theme', 'locale', 'last_login_at', 'last_login_ip',
            ]);
        });
    }
};
