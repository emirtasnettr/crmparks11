<?php

use App\Modules\Agency\Models\Agency;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $used = [];

        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
                continue;
            }

            $rows = DB::table($table)
                ->orderBy('id')
                ->get(['id', 'public_id']);

            foreach ($rows as $row) {
                $current = is_string($row->public_id) ? $row->public_id : null;

                if (
                    $current !== null
                    && $current !== ''
                    && ! isset($used[$current])
                    && preg_match('/^\d{1,8}$/', $current) === 1
                ) {
                    $used[$current] = $table.':'.$row->id;

                    continue;
                }

                $candidate = $this->nextPublicId($used);
                DB::table($table)->where('id', $row->id)->update(['public_id' => $candidate]);
                $used[$candidate] = $table.':'.$row->id;
            }
        }
    }

    public function down(): void
    {
        // Geri alınamaz: çakışan numaralar yeniden dağıtılmıştır.
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        return ['businesses', 'couriers', 'agencies'];
    }

    /**
     * @param  array<string, string>  $used
     */
    private function nextPublicId(array &$used): string
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $candidate = (string) random_int(10_000_000, 99_999_999);

            if (! isset($used[$candidate])) {
                return $candidate;
            }
        }

        throw new RuntimeException('Benzersiz public_id üretilemedi.');
    }
};
