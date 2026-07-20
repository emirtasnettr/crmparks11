<?php

namespace App\Console\Commands;

use App\Support\DemoDataGuard;
use App\Support\OperationalDataCleaner;
use Illuminate\Console\Command;

class WipeOperationalDataCommand extends Command
{
    protected $signature = 'crmlog:wipe-data
                            {--force : Onay sormadan çalıştır (yalnızca local/testing)}';

    protected $description = 'Tüm operasyonel/test verisini siler; süper admin ve sistem katalogları kalır. Production\'da çalışmaz.';

    public function handle(): int
    {
        try {
            DemoDataGuard::assertAllowed();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->warn('İşletme, kurye, acente, finans ve süper admin dışındaki kullanıcılar silinecek.');

        if (! $this->option('force') && ! $this->confirm('Devam edilsin mi?', false)) {
            $this->info('İptal edildi.');

            return self::SUCCESS;
        }

        $counts = OperationalDataCleaner::wipeKeepingSuperAdmins();
        $total = array_sum($counts);

        $this->newLine();
        $this->info("Temizlik tamamlandı ({$total} kayıt).");

        foreach ($counts as $table => $count) {
            $this->line("  - {$table}: {$count}");
        }

        $this->newLine();
        $this->comment('Süper admin hesapları korundu. Demo veri için: php artisan crmlog:seed-demo');

        return self::SUCCESS;
    }
}
