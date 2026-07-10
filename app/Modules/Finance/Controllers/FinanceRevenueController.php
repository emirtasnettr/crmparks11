<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\RevenueFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Requests\StoreRevenueRequest;
use App\Modules\Finance\Requests\UpdateRevenueRequest;
use App\Modules\Finance\Services\RevenuePresenter;
use App\Modules\Finance\Services\RevenueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceRevenueController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly RevenueService $service,
        private readonly RevenuePresenter $presenter,
    ) {}

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

        $all = $this->service->filter($filters)->all();
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.revenues.index', [
            'revenues' => $items,
            'filters' => $filters,
            'businesses' => $this->service->businesses(),
            'revenueTypes' => RevenueFormData::revenueTypes(),
            'collectionStatuses' => RevenueFormData::collectionStatuses(),
            'invoiceStatuses' => RevenueFormData::invoiceStatuses(),
            'dateRanges' => RevenueFormData::dateRanges(),
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StoreRevenueRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('finance.revenues.index')
            ->with('success', 'Gelir kaydı başarıyla oluşturuldu.');
    }

    public function update(UpdateRevenueRequest $request, int $id): RedirectResponse
    {
        $revenue = $this->service->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('finance.revenues.show', $revenue->id)
            ->with('success', 'Gelir kaydı başarıyla güncellendi.');
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
        $revenue = $this->service->find($id);

        abort_if($revenue === null, 404);

        return view('modules.finance.revenues.show', [
            'revenue' => $this->presenter->showRow($revenue),
            'businesses' => $this->service->businesses(),
            'revenueTypes' => RevenueFormData::revenueTypes(),
            'collectionStatuses' => RevenueFormData::collectionStatuses(),
        ]);
    }
}
