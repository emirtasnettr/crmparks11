<?php

namespace App\Console\Commands;

use App\Support\TurkeyLocationDataset;
use Database\Seeders\CitySeeder;
use Database\Seeders\NeighborhoodSeeder;
use Illuminate\Console\Command;

class SyncTurkeyLocationsCommand extends Command
{
    protected $signature = 'locations:sync-turkey
                            {--seed : İndirdikten sonra cities/districts/neighborhoods tablolarını güncelle}';

    protected $description = 'Türkiye il/ilçe/mahalle verisini TurkiyeAPI 2025 kaynağından günceller';

    public function handle(TurkeyLocationDataset $dataset): int
    {
        $this->info('TurkiyeAPI '.TurkeyLocationDataset::DATASET_VERSION.' indiriliyor...');

        $stats = $dataset->refreshFromRemote();

        $this->info(sprintf(
            'Yerel veri güncellendi: %d il, %d ilçe, %d mahalle (v%s)',
            $stats['cities'],
            $stats['districts'],
            $stats['neighborhoods'],
            $stats['version'],
        ));

        if ($this->option('seed')) {
            $this->info('Veritabanı seed ediliyor...');
            $this->call(CitySeeder::class);
            $this->call(NeighborhoodSeeder::class);
            $this->info('Veritabanı güncellendi.');
        }

        return self::SUCCESS;
    }
}
