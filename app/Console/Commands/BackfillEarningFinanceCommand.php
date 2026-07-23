<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Finance\Services\RevenueService;
use Illuminate\Console\Command;

class BackfillEarningFinanceCommand extends Command
{
    protected $signature = 'crmlog:finance:backfill-earning-links';

    protected $description = 'Hakediş gelirlerinin işletme carisi alacaklarını ve eksik kurye yükümlülüklerini tamamlar.';

    public function handle(RevenueService $revenues, PaymentService $payments): int
    {
        $actor = User::query()->role('super_admin')->orderBy('id')->first();

        if ($actor === null) {
            $this->error('Süper admin bulunamadı.');

            return self::FAILURE;
        }

        $receivables = $revenues->backfillEarningReceivables($actor);
        $liabilities = $payments->backfillEarningLiabilities($actor);

        $this->info(sprintf(
            'Backfill: %d işletme alacağı, %d kurye/acente yükümlülüğü yazıldı.',
            $receivables,
            $liabilities,
        ));

        return self::SUCCESS;
    }
}
