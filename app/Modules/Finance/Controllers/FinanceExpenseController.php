<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceExpenseDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceExpenseController extends Controller
{
    use DownloadsListExport;
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

        $all = FinanceExpenseDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.finance.expenses.index', [
            'expenses' => $items,
            'filters' => $filters,
            'expenseTypes' => FinanceExpenseDummyData::expenseTypes(),
            'paymentStatuses' => FinanceExpenseDummyData::paymentStatuses(),
            'dateRanges' => FinanceExpenseDummyData::dateRanges(),
            'couriers' => FinanceExpenseDummyData::couriers(),
            'agencies' => FinanceExpenseDummyData::agencies(),
            'summary' => FinanceExpenseDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
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

    public function show(int $id): View
    {
        $expense = FinanceExpenseDummyData::find($id);

        abort_if($expense === null, 404);

        return view('modules.finance.expenses.show', [
            'expense' => $expense,
        ]);
    }
}
