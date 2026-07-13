<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessContractFormData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Models\Business;
use App\Modules\Business\Requests\StoreBusinessContractRequest;
use App\Modules\Business\Services\BusinessContractPresenter;
use App\Modules\Business\Services\BusinessContractService;
use App\Modules\Business\Services\BusinessDocumentService;
use App\Support\EntityCardRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessContractController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly BusinessContractService $contracts,
        private readonly BusinessContractPresenter $presenter,
        private readonly BusinessDocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(\App\Modules\Business\Support\BusinessCardVisibility::canViewRestrictedTabs($request->user()), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'contract_type' => $request->string('contract_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'end_date' => $request->string('end_date')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->contracts->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($contract) => $this->presenter->indexRow($contract))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.contracts.index', [
            'contracts' => $items,
            'filters' => $filters,
            'businesses' => $this->contracts->businesses(),
            'contractTypes' => BusinessContractFormData::contractTypes(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        abort_unless(\App\Modules\Business\Support\BusinessCardVisibility::canViewRestrictedTabs($request->user()), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'contract_type' => $request->string('contract_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'end_date' => $request->string('end_date')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'isletme-sozlesmeleri',
            BusinessListExportSheets::contracts($filters),
            'İşletme Sözleşmeleri',
        );
    }

    public function show(int $id): View
    {
        abort_unless(\App\Modules\Business\Support\BusinessCardVisibility::canViewRestrictedTabs(request()->user()), 403);

        $contract = $this->contracts->find($id);

        abort_if($contract === null, 404);

        return view('modules.business.contracts.show', [
            'contract' => $this->presenter->showRow($contract),
        ]);
    }

    public function store(StoreBusinessContractRequest $request): RedirectResponse
    {
        $contract = $this->contracts->create($request->validated(), $request->user());

        if ($request->hasFile('contract_file')) {
            $business = Business::query()->findOrFail($contract->contractable_id);
            $this->documents->storeContractFile($business, $request->file('contract_file'), $request->user());
        }

        if ($request->boolean('redirect_to_business')) {
            return EntityCardRedirect::toShow(
                route('businesses.show', $contract->contractable_id),
                'contracts',
                'Sözleşme başarıyla oluşturuldu.',
            );
        }

        return redirect()
            ->route('businesses.contracts.index', ['business_id' => $contract->contractable_id])
            ->with('success', 'Sözleşme başarıyla oluşturuldu.');
    }

    public function deactivate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('business.update'), 403);

        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $this->contracts->deactivate($contract);

        return EntityCardRedirect::after(
            route('businesses.contracts.index', ['business_id' => $contract->contractable_id]),
            'Sözleşme pasife alındı.',
            route('businesses.show', $contract->contractable_id),
            'contracts',
        );
    }
}
