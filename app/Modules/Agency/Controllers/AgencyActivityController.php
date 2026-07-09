<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyActivityFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Services\AgencyActivityPresenter;
use App\Modules\Agency\Services\AgencyActivityService;
use App\Support\RequestFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyActivityController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly AgencyActivityService $activities,
        private readonly AgencyActivityPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'agency_id' => RequestFilter::valueOrAll($request, 'agency_id'),
            'action' => RequestFilter::valueOrAll($request, 'action'),
            'user_id' => RequestFilter::valueOrAll($request, 'user_id'),
            'date_range' => RequestFilter::valueOrAll($request, 'date_range'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->activities->filter($filters);
        $summary = $this->activities->summary();
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($log) => $this->presenter->indexRow($log))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.activities.index', [
            'activities' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'agencies' => $this->activities->agencies(),
            'users' => $this->activities->users(),
            'actionTypes' => AgencyActivityFormData::actionTypes(),
            'dateRanges' => AgencyActivityFormData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'agency_id' => RequestFilter::valueOrAll($request, 'agency_id'),
            'action' => RequestFilter::valueOrAll($request, 'action'),
            'user_id' => RequestFilter::valueOrAll($request, 'user_id'),
            'date_range' => RequestFilter::valueOrAll($request, 'date_range'),
        ];

        return $this->downloadExportSheet(
            'acente-hareket-gecmisi',
            AgencyListExportSheets::activities($filters),
            'Acente Hareket Geçmişi',
        );
    }
}
