<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyActivityDummyData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyActivityController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'action' => $request->string('action')->toString() ?: 'all',
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $allRecords = AgencyActivityDummyData::all();
        $all = AgencyActivityDummyData::filter($filters);
        $summary = AgencyActivityDummyData::summarize($allRecords);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.activities.index', [
            'activities' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'agencies' => AgencyActivityDummyData::agencies(),
            'users' => AgencyActivityDummyData::users(),
            'actionTypes' => AgencyActivityDummyData::actionTypes(),
            'dateRanges' => AgencyActivityDummyData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'action' => $request->string('action')->toString() ?: 'all',
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-hareket-gecmisi',
            AgencyListExportSheets::activities($filters),
            'Acente Hareket Geçmişi',
        );
    }
}
