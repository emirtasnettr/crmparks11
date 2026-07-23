<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Business\Services\BusinessEarningService;
use Illuminate\Console\Command;

class AutoApprovePendingEarningsCommand extends Command
{
    protected $signature = 'crmlog:earnings:auto-approve-pending';

    protected $description = 'Hakediş onay süreci "otomatik" ise bekleyen (taslak/inceleme) satırları onaylar ve finansa işler.';

    public function handle(BusinessEarningService $earnings): int
    {
        if ($earnings->approvalProcess() !== 'auto') {
            $this->warn('Onay süreci "auto" değil; işlem yapılmadı. Ayarlar > Hakedişler > Otomatik Onay.');

            return self::FAILURE;
        }

        $actor = User::query()->role('super_admin')->orderBy('id')->first();

        $result = $earnings->approveAllPendingWhenAuto($actor);

        $this->info(sprintf(
            'Otomatik onay: %d onaylandı, %d atlandı.',
            $result['approved'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
