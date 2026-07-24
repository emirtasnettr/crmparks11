<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Finance\Services\EarningFinanceSyncService;
use App\Modules\Finance\Services\PaymentService;
use App\Modules\Finance\Services\RevenueService;
use Illuminate\Console\Command;

class BackfillEarningFinanceCommand extends Command
{
    protected $signature = 'crmlog:finance:backfill-earning-links
                            {--cleanup-orphans : Soft-delete edilmiş hakedişlerin finans artıklarını temizle}';

    protected $description = 'Hakediş gelirlerinin işletme carisi alacaklarını ve eksik kurye yükümlülüklerini tamamlar.';

    public function handle(
        RevenueService $revenues,
        PaymentService $payments,
        EarningFinanceSyncService $earningFinance,
    ): int {
        $actor = User::query()->role('super_admin')->orderBy('id')->first();

        if ($actor === null) {
            $this->error('Süper admin bulunamadı.');

            return self::FAILURE;
        }

        if ($this->option('cleanup-orphans')) {
            $cleaned = $earningFinance->cleanupOrphanFinance($actor);
            $this->info(sprintf(
                'Orphan temizlik: %d ödeme, %d gelir, %d gider iptal edildi.',
                $cleaned['payments'],
                $cleaned['revenues'],
                $cleaned['expenses'],
            ));
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
