<?php

namespace App\Console\Commands;

use App\Support\DemoDataGuard;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class SeedDemoDataCommand extends Command
{
    protected $signature = 'crmlog:seed-demo
                            {--force : Onay sormadan çalıştır (yalnızca local/testing)}';

    protected $description = 'Yerel test için örnek veri yükler. Production/canlıda çalışmaz.';

    public function handle(): int
    {
        try {
            DemoDataGuard::assertAllowed();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->warn('Bu komut yalnızca geliştirme/test içindir. Canlıya asla uygulanmamalıdır.');
        $this->line('Kapsam: işletme, kurye, acente, vardiya, stok, finans.');
        $this->line('Canlı senaryolar: aktif · geç · girmedi · yaklaşan (1 saat içinde) · eksik kadro.');
        $this->line('Temizlik: php artisan crmlog:clear-demo  (Süper Admin etkilenmez)');

        if (! $this->option('force') && ! $this->confirm('Örnek veriler yüklensin mi?', true)) {
            $this->info('İptal edildi.');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--class' => DemoDataSeeder::class,
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('Örnek veriler yüklendi.');
        $this->line('  Giriş: admin@crmlog.com / password');
        $this->comment('Temizlemek için: php artisan crmlog:clear-demo --force');

        return self::SUCCESS;
    }
}
