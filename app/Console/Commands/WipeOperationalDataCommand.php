<?php

namespace App\Console\Commands;

use App\Support\DemoDataGuard;
use App\Support\OperationalDataCleaner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class WipeOperationalDataCommand extends Command
{
    protected $signature = 'crmlog:wipe-data
                            {--force : Onay sormadan çalıştır}
                            {--allow-production : Production ortamında da çalıştır (geri alınamaz)}';

    protected $description = 'Tüm operasyonel/test verisini siler; süper admin ve sistem katalogları kalır.';

    public function handle(): int
    {
        if (! $this->isEnvironmentAllowed()) {
            return self::FAILURE;
        }

        $this->warn('İşletme, kurye, acente, finans ve süper admin dışındaki tüm kullanıcılar silinecek.');
        $this->line('Süper admin hesapları korunur.');

        if (! $this->option('force') && ! $this->confirm('Devam edilsin mi?', false)) {
            $this->info('İptal edildi.');

            return self::SUCCESS;
        }

        if (app()->isProduction() && $this->option('allow-production') && ! $this->option('force')) {
            if (! $this->confirmProductionWipe()) {
                $this->info('İptal edildi.');

                return self::SUCCESS;
            }
        }

        $counts = OperationalDataCleaner::wipeKeepingSuperAdmins(
            ignoreEnvironmentGuard: $this->option('allow-production') && app()->isProduction()
        );
        $total = array_sum($counts);

        $this->newLine();
        $this->info("Temizlik tamamlandı ({$total} kayıt).");

        foreach ($counts as $table => $count) {
            $this->line("  - {$table}: {$count}");
        }

        $this->newLine();
        $this->comment('Süper admin hesapları korundu.');

        return self::SUCCESS;
    }

    private function isEnvironmentAllowed(): bool
    {
        if (DemoDataGuard::isAllowed()) {
            return true;
        }

        if ($this->option('allow-production')) {
            $this->components->warn('Production ortamında operasyonel veri siliniyor. Bu işlem geri alınamaz.');

            return true;
        }

        $env = (string) app()->environment();
        $this->error("Bu komut yalnızca local/testing ortamında çalışır. Mevcut ortam: [{$env}].");
        $this->line('Canlıda temizlemek için: php artisan crmlog:wipe-data --allow-production');

        return false;
    }

    private function confirmProductionWipe(): bool
    {
        $token = Str::upper(Str::random(6));
        $answer = $this->ask("Production temizliğini onaylamak için şunu yazın: {$token}");

        return is_string($answer) && hash_equals($token, Str::upper(trim($answer)));
    }
}
