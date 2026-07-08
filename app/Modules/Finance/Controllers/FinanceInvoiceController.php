<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceInvoiceDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceInvoiceController extends Controller
{
    use DownloadsListExport;
    public function index(Request $request): View
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'invoice_type' => $request->string('invoice_type')->toString() ?: 'all',
            'invoice_status' => $request->string('invoice_status')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = FinanceInvoiceDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.invoices.index', [
            'invoices' => $items,
            'filters' => $filters,
            'businesses' => FinanceInvoiceDummyData::businesses(),
            'earningOptions' => FinanceInvoiceDummyData::earningOptions(),
            'invoiceTypes' => FinanceInvoiceDummyData::invoiceTypes(),
            'invoiceStatuses' => FinanceInvoiceDummyData::invoiceStatuses(),
            'collectionStatuses' => FinanceInvoiceDummyData::collectionStatuses(),
            'dateRanges' => FinanceInvoiceDummyData::dateRanges(),
            'summary' => FinanceInvoiceDummyData::summarize($filters),
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
            'invoice_type' => $request->string('invoice_type')->toString() ?: 'all',
            'invoice_status' => $request->string('invoice_status')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'faturalar',
            FinanceListExportSheets::invoices($filters),
            'Faturalar',
        );
    }

    public function show(int $id): View
    {
        $invoice = FinanceInvoiceDummyData::find($id);

        abort_if($invoice === null, 404);

        return view('modules.finance.invoices.show', [
            'invoice' => $invoice,
        ]);
    }
}
