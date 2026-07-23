<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\ShiftPlanning\Services\AttendanceEarningSyncService;
use App\Modules\Business\Services\BusinessEarningService;
use Illuminate\Console\Command;

class SyncAttendanceEarningsCommand extends Command
{
    protected $signature = 'crmlog:earnings:sync-from-attendance
                            {--courier_id= : Yalnızca bu kurye}
                            {--business_id= : Yalnızca bu işletme}
                            {--year= : Dönem yılı}
                            {--month= : Dönem ayı (1-12)}';

    protected $description = 'Tamamlanan vardiya katılımlarından gün + çalışma modeli bazında taslak hakediş satırları üretir/günceller.';

    public function handle(AttendanceEarningSyncService $sync, BusinessEarningService $earnings): int
    {
        $filters = array_filter([
            'courier_id' => $this->option('courier_id') !== null ? (int) $this->option('courier_id') : null,
            'business_id' => $this->option('business_id') !== null ? (int) $this->option('business_id') : null,
            'period_year' => $this->option('year') !== null ? (int) $this->option('year') : null,
            'period_month' => $this->option('month') !== null ? (int) $this->option('month') : null,
        ], fn ($value) => $value !== null && $value !== 0);

        $actor = User::query()->role('super_admin')->orderBy('id')->first();

        $result = $sync->sync($actor, $filters);

        $this->info(sprintf(
            'Vardiya hakediş sync: %d oluşturuldu, %d güncellendi, %d atlandı.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));

        if ($earnings->approvalProcess() === 'auto') {
            $approved = $earnings->approveAllPendingWhenAuto($actor);
            $this->info(sprintf(
                'Otomatik onay: %d onaylandı, %d atlandı.',
                $approved['approved'],
                $approved['skipped'],
            ));
        }

        return self::SUCCESS;
    }
}
