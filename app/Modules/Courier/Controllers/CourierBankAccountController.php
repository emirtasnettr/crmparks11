<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierBankAccountFormData;
use App\Modules\Courier\Requests\StoreCourierBankAccountRequest;
use App\Modules\Courier\Services\CourierBankAccountPresenter;
use App\Modules\Courier\Services\CourierBankAccountService;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierBankAccountController extends Controller
{
    public function __construct(
        private readonly CourierBankAccountService $bankAccounts,
        private readonly CourierBankAccountPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'courier_id' => RequestFilter::valueOrAll($request, 'courier_id'),
            'bank_key' => RequestFilter::valueOrAll($request, 'bank_key'),
            'is_default' => RequestFilter::valueOrAll($request, 'is_default'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->bankAccounts->filter($filters);
        $summary = $this->bankAccounts->summary();
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($account) => $this->presenter->indexRow($account))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.bank-accounts.index', [
            'accounts' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => $this->bankAccounts->couriers(),
            'banks' => CourierBankAccountFormData::banks(),
            'statuses' => CourierBankAccountFormData::statuses(),
            'defaultFilters' => CourierBankAccountFormData::defaultFilters(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $account = $this->bankAccounts->find($id);

        abort_if($account === null, 404);

        return view('modules.courier.bank-accounts.show', [
            'account' => $this->presenter->showRow($account),
            'courierAccounts' => $this->bankAccounts->forCourier($account->courier_id)
                ->map(fn ($item) => $this->presenter->indexRow($item))
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreCourierBankAccountRequest $request): RedirectResponse
    {
        $account = $this->bankAccounts->create($request->validated());

        if ($request->boolean('redirect_to_courier')) {
            return redirect()
                ->route('couriers.show', $account->courier_id)
                ->with('success', 'Banka hesabı başarıyla kaydedildi.');
        }

        return redirect()
            ->route('couriers.bank-accounts.index', ['courier_id' => $account->courier_id])
            ->with('success', 'Banka hesabı başarıyla kaydedildi.');
    }
}
