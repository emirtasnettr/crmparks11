<?php

namespace App\Console\Commands;

use App\Support\DemoDataCleaner;
use App\Support\DemoDataGuard;
use Illuminate\Console\Command;

class ClearDemoDataCommand extends Command
{
    protected $signature = 'crmlog:clear-demo
                            {--force : Onay sormadan çalıştır (yalnızca local/testing)}';

    protected $description = 'DEMO_SEED örnek verisini siler. Production/canlıda çalışmaz.';

    public function handle(): int
    {
        try {
            DemoDataGuard::assertAllowed();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->warn('DEMO_SEED örnek verisi silinecek. Süper Admin ve kataloglar (şehir, rol) kalır.');

        if (! $this->option('force') && ! $this->confirm('Örnek (demo) veriler silinsin mi?', false)) {
            $this->info('İptal edildi.');

            return self::SUCCESS;
        }

        $counts = DemoDataCleaner::clear();
        $total = array_sum($counts);

        $this->newLine();
        $this->info("Örnek veriler temizlendi ({$total} kayıt). Süper Admin korundu.");

        foreach ($counts as $table => $count) {
            if ($count > 0) {
                $this->line("  - {$table}: {$count}");
            }
        }

        $this->newLine();
        $this->comment('Yeniden yüklemek için: php artisan crmlog:seed-demo --force');

        return self::SUCCESS;
    }
}
