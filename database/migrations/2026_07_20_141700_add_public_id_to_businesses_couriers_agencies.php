<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['businesses', 'couriers', 'agencies'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->string('public_id', 8)->nullable()->after('uuid');
                $blueprint->unique('public_id', $this->uniqueIndexName($table));
            });
        }

        $usedLookup = [];

        foreach (['businesses', 'couriers', 'agencies'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            foreach (DB::table($table)->whereNotNull('public_id')->pluck('public_id') as $existing) {
                $usedLookup[(string) $existing] = true;
            }
        }

        $this->backfill('businesses', $usedLookup);
        $this->backfill('couriers', $usedLookup);
        $this->backfill('agencies', $usedLookup);
    }

    public function down(): void
    {
        foreach (['businesses', 'couriers', 'agencies'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique($this->uniqueIndexName($table));
                $blueprint->dropColumn('public_id');
            });
        }
    }

    /**
     * @param  array<string, bool>  $usedLookup
     */
    private function backfill(string $table, array &$usedLookup): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
            return;
        }

        $rows = DB::table($table)->whereNull('public_id')->orderBy('id')->get(['id']);

        foreach ($rows as $row) {
            $candidate = $this->nextPublicId($usedLookup);
            DB::table($table)->where('id', $row->id)->update(['public_id' => $candidate]);
            $usedLookup[$candidate] = true;
        }
    }

    /**
     * @param  array<string, bool>  $usedLookup
     */
    private function nextPublicId(array &$usedLookup): string
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $candidate = (string) random_int(10_000_000, 99_999_999);

            if (! isset($usedLookup[$candidate])) {
                return $candidate;
            }
        }

        throw new RuntimeException('Benzersiz public_id üretilemedi.');
    }

    private function uniqueIndexName(string $table): string
    {
        return match ($table) {
            'businesses' => 'businesses_public_id_unique',
            'couriers' => 'couriers_public_id_unique',
            'agencies' => 'agencies_public_id_unique',
            default => $table.'_public_id_unique',
        };
    }
};
