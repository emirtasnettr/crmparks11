<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\FinanceCurrentAccountDummyData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceCurrentAccountController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: '',
            'type' => $request->string('type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'balance_status' => $request->string('balance_status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = FinanceCurrentAccountDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        $accountDetails = collect($items)
            ->keyBy('id')
            ->map(fn (array $account) => [
                'id' => $account['id'],
                'code' => $account['code'],
                'type' => $account['type'],
                'type_label' => $account['type_label'],
                'title' => $account['title'],
                'phone' => $account['phone'],
                'email' => $account['email'],
                'city' => $account['city'],
                'tax_number' => $account['tax_number'],
                'status' => $account['status'],
                'status_label' => $account['status_label'],
                'total_debit' => $account['total_debit'],
                'total_credit' => $account['total_credit'],
                'total_debit_formatted' => $account['total_debit_formatted'],
                'total_credit_formatted' => $account['total_credit_formatted'],
                'balance' => $account['balance'],
                'balance_formatted' => $account['balance_formatted'],
                'balance_tone' => $account['balance_tone'],
                'last_invoice' => $account['last_invoice'],
                'last_earning' => $account['last_earning'],
                'recent_movements' => $account['recent_movements'],
                'movements' => $account['movements'],
            ])
            ->all();

        return view('modules.finance.current-accounts.index', [
            'accounts' => $items,
            'accountDetails' => $accountDetails,
            'accountOptions' => FinanceCurrentAccountDummyData::options(),
            'filters' => $filters,
            'accountTypes' => FinanceCurrentAccountDummyData::accountTypes(),
            'statuses' => FinanceCurrentAccountDummyData::statuses(),
            'balanceStatuses' => FinanceCurrentAccountDummyData::balanceStatuses(),
            'transactionTypes' => FinanceCurrentAccountDummyData::transactionTypes(),
            'summary' => FinanceCurrentAccountDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: '',
            'type' => $request->string('type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'balance_status' => $request->string('balance_status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'cari-hesaplar',
            FinanceListExportSheets::currentAccounts($filters),
            'Cari Hesaplar',
        );
    }
}
