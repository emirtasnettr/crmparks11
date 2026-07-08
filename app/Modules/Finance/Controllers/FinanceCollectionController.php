<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceCollectionDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceCollectionController extends Controller
{
    use DownloadsListExport;
    public function index(Request $request): View
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'payment_method' => $request->string('payment_method')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'due_date' => $request->string('due_date')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = FinanceCollectionDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.collections.index', [
            'collections' => $items,
            'filters' => $filters,
            'businesses' => FinanceCollectionDummyData::businesses(),
            'revenueOptions' => FinanceCollectionDummyData::revenueOptions(),
            'collectionStatuses' => FinanceCollectionDummyData::collectionStatuses(),
            'paymentMethods' => FinanceCollectionDummyData::paymentMethods(),
            'dateRanges' => FinanceCollectionDummyData::dateRanges(),
            'dueDateFilters' => FinanceCollectionDummyData::dueDateFilters(),
            'summary' => FinanceCollectionDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'payment_method' => $request->string('payment_method')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'due_date' => $request->string('due_date')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'tahsilatlar',
            FinanceListExportSheets::collections($filters),
            'Tahsilatlar',
        );
    }

    public function show(int $id): View
    {
        $collection = FinanceCollectionDummyData::find($id);

        abort_if($collection === null, 404);

        return view('modules.finance.collections.show', [
            'collection' => $collection,
        ]);
    }
}
