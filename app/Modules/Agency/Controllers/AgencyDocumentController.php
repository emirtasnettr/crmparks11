<?php

namespace App\Modules\Agency\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyDocumentDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgencyDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'document_type' => $request->string('document_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'expiry_filter' => $request->string('expiry_filter')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = AgencyDocumentDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.documents.index', [
            'documents' => $items,
            'filters' => $filters,
            'agencies' => AgencyDocumentDummyData::agencies(),
            'documentTypes' => AgencyDocumentDummyData::documentTypes(),
            'statuses' => AgencyDocumentDummyData::statuses(),
            'expiryFilters' => AgencyDocumentDummyData::expiryFilters(),
            'summary' => AgencyDocumentDummyData::summary($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $document = AgencyDocumentDummyData::find($id);

        abort_if($document === null, 404);

        return view('modules.agency.documents.show', [
            'document' => $document,
        ]);
    }
}
