<?php

namespace App\Modules\Report\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Report\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        return view('modules.report.index', [
            'reports' => $this->reports->catalog($request->user()),
        ]);
    }

    public function earnings(Request $request): View
    {
        $filters = [
            'year' => $request->string('year')->toString() ?: (string) now()->year,
            'month' => $request->string('month')->toString() ?: 'all',
        ];

        $data = $this->reports->earningsSummary($filters);

        return view('modules.report.earnings', [
            'filters' => $data['filters'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
            'months' => array_merge(['all' => 'Tümü'], BusinessEarningFormData::months()),
            'years' => $this->yearOptions(),
        ]);
    }

    public function earningsExport(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('report.export'), 403);

        $filters = [
            'year' => $request->string('year')->toString() ?: (string) now()->year,
            'month' => $request->string('month')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'hakedis-ozeti',
            $this->reports->earningsExportRows($filters),
            'Hakediş Özeti',
        );
    }

    public function collections(Request $request): View
    {
        abort_unless($request->user()?->can('dashboard.financial'), 403);

        $data = $this->reports->collectionsAging();

        return view('modules.report.collections', [
            'buckets' => $data['buckets'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
        ]);
    }

    public function collectionsExport(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('dashboard.financial') && $request->user()?->can('report.export'), 403);

        return $this->downloadExportSheet(
            'tahsilat-yaslandirma',
            $this->reports->collectionsExportRows(),
            'Tahsilat Yaşlandırma',
        );
    }

    public function operations(): View
    {
        return view('modules.report.operations', [
            'stats' => $this->reports->operationsSummary()['stats'],
        ]);
    }

    public function courierPerformance(Request $request): View
    {
        $filters = [
            'year' => $request->string('year')->toString() ?: (string) now()->year,
            'month' => $request->string('month')->toString() ?: 'all',
        ];

        $data = $this->reports->courierPerformanceSummary($filters);

        return view('modules.report.courier-performance', [
            'filters' => $data['filters'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
            'months' => array_merge(['all' => 'Tümü'], BusinessEarningFormData::months()),
            'years' => $this->yearOptions(),
        ]);
    }

    public function courierPerformanceExport(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('report.export'), 403);

        $filters = [
            'year' => $request->string('year')->toString() ?: (string) now()->year,
            'month' => $request->string('month')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'kurye-performansi',
            $this->reports->courierPerformanceExportRows($filters),
            'Kurye Performansı',
        );
    }

    public function agencyShare(Request $request): View
    {
        $filters = [
            'year' => $request->string('year')->toString() ?: (string) now()->year,
            'month' => $request->string('month')->toString() ?: 'all',
        ];

        $data = $this->reports->agencyShareSummary($filters);

        return view('modules.report.agency-share', [
            'filters' => $data['filters'],
            'summary' => $data['summary'],
            'rows' => $data['rows'],
            'months' => array_merge(['all' => 'Tümü'], BusinessEarningFormData::months()),
            'years' => $this->yearOptions(),
        ]);
    }

    public function agencyShareExport(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->can('report.export'), 403);

        $filters = [
            'year' => $request->string('year')->toString() ?: (string) now()->year,
            'month' => $request->string('month')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-payi',
            $this->reports->agencyShareExportRows($filters),
            'Acente Payı',
        );
    }

    /**
     * @return array<string, string>
     */
    private function yearOptions(): array
    {
        $current = (int) now()->year;
        $years = [];

        for ($year = $current; $year >= $current - 4; $year--) {
            $years[(string) $year] = (string) $year;
        }

        return $years;
    }
}
