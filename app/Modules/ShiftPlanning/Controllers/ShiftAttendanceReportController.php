<?php

namespace App\Modules\ShiftPlanning\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\ShiftPlanning\Services\ShiftAttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShiftAttendanceReportController extends Controller
{
    use DownloadsListExport;

    /** @var array<string, string> */
    public const STATUS_OPTIONS = [
        'missing' => 'Girmedi',
        'late' => 'Geç',
        'in_progress' => 'Devam ediyor',
        'completed' => 'Geldi',
        'planned' => 'Planlandı',
    ];

    public function __construct(
        private readonly ShiftAttendanceReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);
        $selectedStatuses = $this->resolveStatuses($request);
        $report = $this->reports->report($from, $to, $selectedStatuses);

        $perPage = 20;
        $page = max(1, (int) $request->query('page', 1));
        $total = count($report['rows']);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $report['rows'] = array_values(array_slice($report['rows'], ($page - 1) * $perPage, $perPage));

        return view('modules.shift-planning.attendance-report', [
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'selectedStatuses' => $selectedStatuses,
            'statusOptions' => self::STATUS_OPTIONS,
            'report' => $report,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $selectedStatuses = $this->resolveStatuses($request);
        $sheet = $this->reports->exportSheet($from, $to, $selectedStatuses);

        return $this->downloadExportSheet(
            'vardiya-raporu-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d'),
            $sheet,
            'Vardiya Raporu',
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $default = Carbon::yesterday()->startOfDay();

        $fromInput = $request->string('from')->toString();
        $toInput = $request->string('to')->toString();

        if ($fromInput === '' && $toInput === '' && filled($request->string('date')->toString())) {
            $day = Carbon::parse($request->string('date')->toString())->startOfDay();

            return [$day, $day];
        }

        $from = filled($fromInput) ? Carbon::parse($fromInput)->startOfDay() : $default->copy();
        $to = filled($toInput) ? Carbon::parse($toInput)->startOfDay() : $from->copy();

        if ($to->lt($from)) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * Boş = tüm durumlar.
     *
     * @return list<string>
     */
    private function resolveStatuses(Request $request): array
    {
        $allowed = array_keys(self::STATUS_OPTIONS);
        $raw = $request->input('status', []);

        if (! is_array($raw)) {
            $raw = filled($raw) ? [(string) $raw] : [];
        }

        return array_values(array_intersect(
            array_map('strval', $raw),
            $allowed,
        ));
    }
}
