<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('muted');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['name' => 'Yeni Başvuru', 'slug' => 'yeni-basvuru', 'color' => 'primary', 'sort_order' => 1, 'is_default' => true],
            ['name' => 'Olumlu', 'slug' => 'olumlu', 'color' => 'success', 'sort_order' => 2, 'is_default' => false],
            ['name' => 'Olumsuz', 'slug' => 'olumsuz', 'color' => 'danger', 'sort_order' => 3, 'is_default' => false],
            ['name' => 'Ulaşılamadı', 'slug' => 'ulasilamadi', 'color' => 'warning', 'sort_order' => 4, 'is_default' => false],
            ['name' => 'Kararsız', 'slug' => 'kararsiz', 'color' => 'muted', 'sort_order' => 5, 'is_default' => false],
        ];

        foreach ($defaults as $status) {
            DB::table('form_submission_statuses')->insert([
                ...$status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->foreignId('form_submission_status_id')
                ->nullable()
                ->after('form_id')
                ->constrained('form_submission_statuses')
                ->nullOnDelete();
        });

        $defaultId = DB::table('form_submission_statuses')->where('is_default', true)->value('id');

        if ($defaultId) {
            DB::table('form_submissions')->whereNull('form_submission_status_id')->update([
                'form_submission_status_id' => $defaultId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_submission_status_id');
        });

        Schema::dropIfExists('form_submission_statuses');
    }
};
