<?php

namespace App\Console\Commands;

use App\Modules\ShiftPlanning\Services\AttendanceEarningSyncService;
use Illuminate\Console\Command;

class DedupeAttendanceEarningsCommand extends Command
{
    protected $signature = 'crmlog:earnings:dedupe-attendance-sync';

    protected $description = 'Aynı gün için mükerrer [vardiya-sync] hakediş satırlarını temizler (taslak/incelemede).';

    public function handle(AttendanceEarningSyncService $sync): int
    {
        $result = $sync->dedupeSyncLines();

        $this->info(sprintf(
            'Mükerrer hakediş temizliği: %d grup, %d satır silindi.',
            $result['groups'],
            $result['removed'],
        ));

        return self::SUCCESS;
    }
}
