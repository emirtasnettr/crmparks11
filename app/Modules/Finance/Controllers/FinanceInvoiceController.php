<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\InvoiceFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Requests\BulkInvoiceRequest;
use App\Modules\Finance\Requests\StoreInvoiceRequest;
use App\Modules\Finance\Requests\UpdateInvoiceRequest;
use App\Modules\Finance\Services\InvoicePresenter;
use App\Modules\Finance\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceInvoiceController extends Controller
{
    use DownloadsListExport;
    use DownloadsPdfExport;

    public function __construct(
        private readonly InvoiceService $service,
        private readonly InvoicePresenter $presenter,
    ) {}

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

        $all = $this->service->filter($filters)->all();
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.invoices.index', [
            'invoices' => $items,
            'filters' => $filters,
            'businesses' => $this->service->businesses(),
            'earningOptions' => $this->service->earningOptions(),
            'invoiceTypes' => InvoiceFormData::invoiceTypes(),
            'invoiceStatuses' => InvoiceFormData::invoiceStatuses(),
            'collectionStatuses' => InvoiceFormData::collectionStatuses(),
            'dateRanges' => InvoiceFormData::dateRanges(),
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('finance.invoices.index')
            ->with('success', 'Fatura kaydı başarıyla oluşturuldu.');
    }

    public function bulk(BulkInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->service->bulkCreateFromEarnings($data['earning_ids'], $data, $request->user());

        $message = "{$result['processed']} fatura oluşturuldu.";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} kayıt atlandı.";
        }

        return redirect()
            ->route('finance.invoices.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function update(UpdateInvoiceRequest $request, int $id): RedirectResponse
    {
        $invoice = $this->service->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('finance.invoices.show', $invoice->id)
            ->with('success', 'Fatura kaydı başarıyla güncellendi.');
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

    public function exportPdf(Request $request): Response
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'invoice_type' => $request->string('invoice_type')->toString() ?: 'all',
            'invoice_status' => $request->string('invoice_status')->toString() ?: 'all',
            'collection_status' => $request->string('collection_status')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $summary = $this->service->summarize($filters);

        return $this->downloadPdfTable(
            'Faturalar',
            FinanceListExportSheets::invoices($filters),
            'faturalar',
            [
                'Toplam' => number_format((float) $summary['total_invoice'], 2).' ₺',
                'Tahsil Edilen' => number_format((float) $summary['collected_amount'], 2).' ₺',
                'Bekleyen' => number_format((float) $summary['pending_amount'], 2).' ₺',
            ],
        );
    }

    public function pdf(int $id): Response
    {
        $invoice = $this->service->find($id);

        abort_if($invoice === null, 404);

        $row = $this->presenter->showRow($invoice);

        return $this->streamPdf('exports.pdf.invoice', [
            'invoice' => $row,
            'title' => 'Fatura '.$row['reference'],
            'subtitle' => $row['invoice_type_label'],
        ], 'fatura-'.$row['reference']);
    }

    public function show(int $id): View
    {
        $invoice = $this->service->find($id);

        abort_if($invoice === null, 404);

        return view('modules.finance.invoices.show', [
            'invoice' => $this->presenter->showRow($invoice),
            'businesses' => $this->service->businesses(),
            'invoiceTypes' => InvoiceFormData::invoiceTypes(),
        ]);
    }
}
