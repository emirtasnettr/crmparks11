<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierBankAccountDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierBankAccountController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'bank_key' => $request->string('bank_key')->toString() ?: 'all',
            'is_default' => $request->string('is_default')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = CourierBankAccountDummyData::filter($filters);
        $summary = CourierBankAccountDummyData::summarize(CourierBankAccountDummyData::all());
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.bank-accounts.index', [
            'accounts' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => CourierBankAccountDummyData::couriers(),
            'banks' => CourierBankAccountDummyData::banks(),
            'statuses' => CourierBankAccountDummyData::statuses(),
            'defaultFilters' => CourierBankAccountDummyData::defaultFilters(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $account = CourierBankAccountDummyData::find($id);

        abort_if($account === null, 404);

        return view('modules.courier.bank-accounts.show', [
            'account' => $account,
            'courierAccounts' => CourierBankAccountDummyData::courierAccounts($account['courier_id']),
        ]);
    }
}
