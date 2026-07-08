<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessDocumentDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'document_type' => $request->string('document_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = BusinessDocumentDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.documents.index', [
            'documents' => $items,
            'filters' => $filters,
            'businesses' => BusinessDocumentDummyData::businesses(),
            'documentTypes' => BusinessDocumentDummyData::documentTypes(),
            'statuses' => BusinessDocumentDummyData::statuses(),
            'dateRanges' => BusinessDocumentDummyData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }
}
