<?php

namespace App\Console\Commands;

use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use Illuminate\Console\Command;

class AutoEndShiftAttendancesCommand extends Command
{
    protected $signature = 'crmlog:shifts:auto-end';

    protected $description = 'Vardiya bitişinden 15 dk sonra hâlâ açık olan katılımları otomatik sonlandırır. Hakediş yalnızca planlanan vardiya süresidir.';

    public function handle(ShiftAttendanceService $attendances): int
    {
        $ended = $attendances->autoEndOverdueAttendances();

        $this->info("Otomatik sonlandırılan vardiya: {$ended}");

        return self::SUCCESS;
    }
}
