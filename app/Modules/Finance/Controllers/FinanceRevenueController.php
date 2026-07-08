<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceRevenueDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceRevenueController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'revenue_type' => $request->string('revenue_type')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'invoice_status' => $request->string('invoice_status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = FinanceRevenueDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.revenues.index', [
            'revenues' => $items,
            'filters' => $filters,
            'businesses' => FinanceRevenueDummyData::businesses(),
            'revenueTypes' => FinanceRevenueDummyData::revenueTypes(),
            'collectionStatuses' => FinanceRevenueDummyData::collectionStatuses(),
            'invoiceStatuses' => FinanceRevenueDummyData::invoiceStatuses(),
            'dateRanges' => FinanceRevenueDummyData::dateRanges(),
            'summary' => FinanceRevenueDummyData::summarize($filters),
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
            'revenue_type' => $request->string('revenue_type')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'invoice_status' => $request->string('invoice_status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'gelirler',
            FinanceListExportSheets::revenues($filters),
            'Gelirler',
        );
    }

    public function show(int $id): View
    {
        $revenue = FinanceRevenueDummyData::find($id);

        abort_if($revenue === null, 404);

        return view('modules.finance.revenues.show', [
            'revenue' => $revenue,
        ]);
    }
}
