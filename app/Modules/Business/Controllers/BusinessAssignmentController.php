<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessAssignmentController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = BusinessAssignmentDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.assignments.index', [
            'assignments' => $items,
            'filters' => $filters,
            'businesses' => BusinessContactDummyData::businesses(),
            'agencies' => BusinessAssignmentDummyData::agencies(),
            'couriers' => BusinessAssignmentDummyData::couriers(),
            'activeCount' => BusinessAssignmentDummyData::countActive(),
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
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'atanan-kuryeler',
            BusinessListExportSheets::assignments($filters),
            'Atanan Kuryeler',
        );
    }

    public function show(int $id): View
    {
        $assignment = BusinessAssignmentDummyData::find($id);

        abort_if($assignment === null, 404);

        return view('modules.business.assignments.show', [
            'assignment' => $assignment,
        ]);
    }
}
