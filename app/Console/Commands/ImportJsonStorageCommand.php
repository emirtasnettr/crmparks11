<?php

namespace App\Console\Commands;

use App\Support\Storage\JsonStorageImporter;
use Illuminate\Console\Command;

class ImportJsonStorageCommand extends Command
{
    protected $signature = 'crmlog:import-json-storage';

    protected $description = 'Mevcut JSON dosya depolamasından MySQL tablolarına veri aktarır (yalnızca boş tablolar)';

    public function handle(JsonStorageImporter $importer): int
    {
        $results = $importer->importIfEmpty();

        foreach ($results as $type => $count) {
            $this->line(sprintf('  %s: %d kayıt', $type, $count));
        }

        $total = array_sum($results);

        if ($total === 0) {
            $this->components->info('Aktarılacak veri bulunamadı veya tablolar zaten dolu.');
        } else {
            $this->components->info("Toplam {$total} kayıt aktarıldı.");
        }

        return self::SUCCESS;
    }
}
