<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessContactController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = BusinessContactDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.contacts.index', [
            'contacts' => $items,
            'filters' => $filters,
            'businesses' => BusinessContactDummyData::businesses(),
            'titles' => BusinessContactDummyData::titles(),
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
            'title' => $request->string('title')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'isletme-yetkilileri',
            BusinessListExportSheets::contacts($filters),
            'İşletme Yetkilileri',
        );
    }
}
