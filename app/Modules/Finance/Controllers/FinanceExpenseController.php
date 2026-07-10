<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Core\Http\Concerns\DownloadsPdfExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\ExpenseFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Requests\StoreExpenseRequest;
use App\Modules\Finance\Requests\UpdateExpenseRequest;
use App\Modules\Finance\Services\ExpensePresenter;
use App\Modules\Finance\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceExpenseController extends Controller
{
    use DownloadsListExport;
    use DownloadsPdfExport;

    public function __construct(
        private readonly ExpenseService $service,
        private readonly ExpensePresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'expense_type' => $request->string('expense_type')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->service->filter($filters)->all();
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.expenses.index', [
            'expenses' => $items,
            'filters' => $filters,
            'expenseTypes' => ExpenseFormData::expenseTypes(),
            'paymentStatuses' => ExpenseFormData::paymentStatuses(),
            'dateRanges' => ExpenseFormData::dateRanges(),
            'couriers' => $this->service->couriers(),
            'agencies' => $this->service->agencies(),
            'summary' => $this->service->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'Gider kaydı başarıyla oluşturuldu.');
    }

    public function update(UpdateExpenseRequest $request, int $id): RedirectResponse
    {
        $expense = $this->service->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('finance.expenses.show', $expense->id)
            ->with('success', 'Gider kaydı başarıyla güncellendi.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->service->delete($id, $request->user());

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'Gider kaydı silindi.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'expense_type' => $request->string('expense_type')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'giderler',
            FinanceListExportSheets::expenses($filters),
            'Giderler',
        );
    }

    public function exportPdf(Request $request): Response
    {
        $filters = [
            'expense_type' => $request->string('expense_type')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        $summary = $this->service->summarize($filters);

        return $this->downloadPdfTable(
            'Giderler',
            FinanceListExportSheets::expenses($filters),
            'giderler',
            [
                'Toplam' => number_format((float) $summary['total_expense'], 2).' ₺',
                'Ödenen' => number_format((float) $summary['paid_amount'], 2).' ₺',
                'Bekleyen' => number_format((float) $summary['pending_payment'], 2).' ₺',
            ],
        );
    }

    public function pdf(int $id): Response
    {
        $expense = $this->service->find($id);

        abort_if($expense === null, 404);

        $row = $this->presenter->showRow($expense);

        return $this->streamPdf('exports.pdf.document', [
            'title' => 'Gider '.$row['reference'],
            'subtitle' => $row['expense_type_label'],
            'fields' => [
                'Gider No' => $row['reference'],
                'Gider Türü' => $row['expense_type_label'],
                'Kurye / Acente' => $row['payee_display'],
                'Belge No' => $row['document_no'] ?? '—',
                'Ödeme Durumu' => $row['payment_status_label'],
                'Ödeme Tarihi' => $row['payment_date_formatted'],
                'Gider Tarihi' => $row['expense_date_formatted'],
                'Açıklama' => $row['description'] ?? '—',
            ],
            'totals' => [
                'Tutar' => $row['amount_formatted'],
                'KDV' => $row['vat_amount_formatted'],
                'Genel Toplam' => $row['gross_amount_formatted'],
            ],
        ], 'gider-'.$row['reference']);
    }

    public function show(int $id): View
    {
        $expense = $this->service->find($id);

        abort_if($expense === null, 404);

        return view('modules.finance.expenses.show', [
            'expense' => $this->presenter->showRow($expense),
            'expenseTypes' => ExpenseFormData::expenseTypes(),
            'paymentStatuses' => ExpenseFormData::paymentStatuses(),
            'couriers' => $this->service->couriers(),
            'agencies' => $this->service->agencies(),
        ]);
    }
}
