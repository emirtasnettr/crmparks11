<?php

namespace App\Console\Commands;

use App\Modules\ShiftPlanning\Services\ShiftAttendanceService;
use Illuminate\Console\Command;

class DedupeOverlappingAttendancesCommand extends Command
{
    protected $signature = 'crmlog:attendance:dedupe-overlapping
                            {--courier= : Sadece bu kurye id}';

    protected $description = 'Aynı gün / işletmede örtüşen vardiya katılımlarını tekilleştirir ve hakedişi yeniden senkronlar.';

    public function handle(ShiftAttendanceService $attendances): int
    {
        $courierId = $this->option('courier');
        $courierId = filled($courierId) ? (int) $courierId : null;

        $result = $attendances->dedupeOverlappingAttendances($courierId);

        $this->info(sprintf(
            'Örtüşen katılım temizliği: %d gün grubu, %d kayıt silindi.',
            $result['groups'],
            $result['removed'],
        ));

        if ($result['courier_ids'] !== []) {
            $this->line('Etkilenen kuryeler: '.implode(', ', $result['courier_ids']));
        }

        return self::SUCCESS;
    }
}
