<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\CollectionFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Requests\BulkCollectRequest;
use App\Modules\Finance\Requests\StoreCollectionReceiptRequest;
use App\Modules\Finance\Requests\StoreCollectionRequest;
use App\Modules\Finance\Requests\UpdateCollectionRequest;
use App\Modules\Finance\Services\CollectionPresenter;
use App\Modules\Finance\Services\CollectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceCollectionController extends Controller
{
    use DownloadsListExport;
    use DownloadsPdfExport;

    public function __construct(
        private readonly CollectionService $service,
        private readonly CollectionPresenter $presenter,
    ) {}

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

        $all = $this->service->filter($filters)->all();
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.collections.index', [
            'collections' => $items,
            'filters' => $filters,
            'businesses' => $this->service->businesses(),
            'revenueOptions' => $this->service->revenueOptions(),
            'collectionStatuses' => CollectionFormData::collectionStatuses(),
            'paymentMethods' => CollectionFormData::paymentMethods(),
            'dateRanges' => CollectionFormData::dateRanges(),
            'dueDateFilters' => CollectionFormData::dueDateFilters(),
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('finance.collections.index')
            ->with('success', 'Tahsilat kaydı başarıyla oluşturuldu.');
    }

    public function bulk(BulkCollectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->service->bulkCollect($data['ids'], $data, $request->user());

        $message = "{$result['processed']} tahsilat işlendi.";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} kayıt atlandı.";
        }

        return redirect()
            ->route('finance.collections.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function update(UpdateCollectionRequest $request, int $id): RedirectResponse
    {
        $collection = $this->service->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('finance.collections.show', $collection->id)
            ->with('success', 'Tahsilat kaydı başarıyla güncellendi.');
    }

    public function storeReceipt(StoreCollectionReceiptRequest $request, int $id): RedirectResponse
    {
        $collection = $this->service->storeReceipt($id, $request->file('file'), $request->user());

        return redirect()
            ->route('finance.collections.show', $collection->id)
            ->with('success', 'Dekont başarıyla yüklendi.');
    }

    public function downloadReceipt(int $id): StreamedResponse
    {
        $collection = $this->service->find($id);

        abort_if($collection === null || ! $collection->receipt_path, 404);

        return Storage::disk('public')->download(
            $collection->receipt_path,
            $collection->receipt_original_name ?: basename($collection->receipt_path),
        );
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

    public function pdf(int $id): Response
    {
        $collection = $this->service->find($id);

        abort_if($collection === null, 404);

        $row = $this->presenter->showRow($collection);

        return $this->streamPdf('exports.pdf.document', [
            'title' => 'Tahsilat '.$row['reference'],
            'subtitle' => $row['status_label'],
            'fields' => [
                'Tahsilat No' => $row['reference'],
                'İşletme' => $row['business_name'],
                'Gelir No' => $row['revenue_reference_display'],
                'Fatura No' => $row['invoice_no_display'],
                'Vade Tarihi' => $row['due_date_formatted'],
                'Tahsilat Tarihi' => $row['collection_date_formatted'],
                'Ödeme Yöntemi' => $row['payment_method_label'],
                'Ödeme Referansı' => $row['payment_reference'] ?? '—',
                'Banka' => $row['bank'] ?? '—',
                'Durum' => $row['status_label'],
            ],
            'totals' => [
                'Toplam Tutar' => $row['total_amount_formatted'],
                'Tahsil Edilen' => $row['collected_amount_formatted'],
                'Kalan' => $row['remaining_amount_formatted'],
            ],
        ], 'tahsilat-'.$row['reference']);
    }

    public function show(int $id): View
    {
        $collection = $this->service->find($id);

        abort_if($collection === null, 404);

        return view('modules.finance.collections.show', [
            'collection' => $this->presenter->showRow($collection),
            'businesses' => $this->service->businesses(),
            'revenueOptions' => $this->service->revenueOptions(),
        ]);
    }
}
