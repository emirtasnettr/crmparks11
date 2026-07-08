<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyCourierDummyData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyCourierController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'vehicle_type' => $request->string('vehicle_type')->toString() ?: 'all',
            'active_business' => $request->string('active_business')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = AgencyCourierDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.couriers.index', [
            'records' => $items,
            'filters' => $filters,
            'agencies' => AgencyCourierDummyData::agencies(),
            'couriers' => AgencyCourierDummyData::couriers(),
            'vehicleTypes' => AgencyCourierDummyData::vehicleTypes(),
            'businesses' => AgencyCourierDummyData::businesses(),
            'summary' => AgencyCourierDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'vehicle_type' => $request->string('vehicle_type')->toString() ?: 'all',
            'active_business' => $request->string('active_business')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-kuryeleri',
            AgencyListExportSheets::couriers($filters),
            'Acente Kuryeleri',
        );
    }
}
