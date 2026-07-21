<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessCommercialContractFormData;
use App\Modules\Business\Requests\StoreBusinessCommercialContractRequest;
use App\Modules\Business\Requests\UpdateBusinessCommercialContractRequest;
use App\Modules\Business\Services\BusinessCommercialContractPresenter;
use App\Modules\Business\Services\BusinessCommercialContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessCommercialContractController extends Controller
{
    public function __construct(
        private readonly BusinessCommercialContractService $contracts,
        private readonly BusinessCommercialContractPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('business.view'), 403);

        $businessId = $request->integer('business_id') ?: null;
        $rows = $businessId
            ? $this->contracts->forBusiness($businessId)
                ->map(fn ($contract) => $this->presenter->indexRow($contract))
                ->values()
                ->all()
            : [];

        return view('modules.business.commercial-contracts.index', [
            'contracts' => $rows,
            'businesses' => $this->contracts->businesses(),
            'selectedBusinessId' => $businessId,
            'workTypes' => BusinessCommercialContractFormData::workTypes(),
            'paymentPeriods' => BusinessCommercialContractFormData::paymentPeriods(),
            'canManage' => $request->user()?->can('business.update') ?? false,
            'canEdit' => $request->user()?->hasRole('super_admin') ?? false,
            'activeContract' => $businessId && ($active = $this->contracts->activeForBusiness($businessId))
                ? $this->presenter->indexRow($active)
                : null,
        ]);
    }

    public function store(StoreBusinessCommercialContractRequest $request): RedirectResponse
    {
        $contract = $this->contracts->create($request->validated(), $request->user());

        return redirect()
            ->route('businesses.show', ['id' => $contract->business_id, 'tab' => 'commercial-contracts'])
            ->with('success', 'Kontrat kaydedildi. Önceki aktif kontrat sonlandırıldı; geçmiş kayıtlar korundu.');
    }

    public function show(Request $request, int $id): View
    {
        abort_unless($request->user()?->can('business.view'), 403);

        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        return view('modules.business.commercial-contracts.show', [
            'contract' => $this->presenter->detail($contract),
            'canManage' => $request->user()?->can('business.update') ?? false,
            'canEdit' => ($request->user()?->hasRole('super_admin') ?? false) && $contract->isActive(),
            'workTypes' => BusinessCommercialContractFormData::workTypes(),
            'paymentPeriods' => BusinessCommercialContractFormData::paymentPeriods(),
        ]);
    }

    public function update(UpdateBusinessCommercialContractRequest $request, int $id): RedirectResponse
    {
        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $this->contracts->update($contract, $request->validated());

        return redirect()
            ->route('businesses.show', ['id' => $contract->business_id, 'tab' => 'commercial-contracts'])
            ->with('success', 'Kontrat güncellendi.');
    }

    public function end(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('business.update'), 403);

        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $this->contracts->end($contract);

        return redirect()
            ->route('businesses.show', ['id' => $contract->business_id, 'tab' => 'commercial-contracts'])
            ->with('success', 'Kontrat sonlandırıldı. Geçmiş hakedişler etkilenmez.');
    }
}
