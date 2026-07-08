<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyContactDummyData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyContactController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = AgencyContactDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.contacts.index', [
            'contacts' => $items,
            'filters' => $filters,
            'agencies' => AgencyContactDummyData::agencies(),
            'titles' => AgencyContactDummyData::titles(),
            'summary' => AgencyContactDummyData::summarize($filters),
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
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-yetkilileri',
            AgencyListExportSheets::contacts($filters),
            'Acente Yetkilileri',
        );
    }

    public function show(int $id): View
    {
        $contact = AgencyContactDummyData::find($id);

        abort_if($contact === null, 404);

        return view('modules.agency.contacts.show', [
            'contact' => $contact,
        ]);
    }
}
