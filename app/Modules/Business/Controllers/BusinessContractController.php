<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessContractFormData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\StoreBusinessContractRequest;
use App\Modules\Business\Requests\UpdateBusinessContractRequest;
use App\Modules\Business\Services\BusinessContractPresenter;
use App\Modules\Business\Services\BusinessContractService;
use App\Modules\Business\Services\BusinessDocumentService;
use App\Support\EntityCardRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'businesses' => $this->contracts->businesses(),
            'contractTypes' => BusinessContractFormData::contractTypes(),
        ]);
    }

    public function store(StoreBusinessContractRequest $request): RedirectResponse
    {
        $contract = $this->contracts->create($request->validated(), $request->user());

        if ($request->hasFile('contract_file')) {
            $this->documents->storeForContract($contract, $request->file('contract_file'), $request->user());
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

    public function update(UpdateBusinessContractRequest $request, int $id): RedirectResponse
    {
        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $contract = $this->contracts->update($contract, $request->validated(), $request->user());

        if ($request->hasFile('contract_file')) {
            $this->documents->storeForContract($contract, $request->file('contract_file'), $request->user());
        }

        if ($request->boolean('redirect_to_contract')) {
            return redirect()
                ->route('businesses.contracts.show', $contract->id)
                ->with('success', 'Sözleşme başarıyla güncellendi.');
        }

        if ($request->boolean('redirect_to_business')) {
            return EntityCardRedirect::toShow(
                route('businesses.show', $contract->contractable_id),
                'contracts',
                'Sözleşme başarıyla güncellendi.',
            );
        }

        return redirect()
            ->route('businesses.contracts.index', ['business_id' => $contract->contractable_id])
            ->with('success', 'Sözleşme başarıyla güncellendi.');
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        abort_unless(\App\Modules\Business\Support\BusinessCardVisibility::canViewRestrictedTabs($request->user()), 403);

        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $document = $this->documents->findForContract($contract);
        abort_if($document === null, 404);

        return Storage::disk($document->disk ?: 'public')
            ->download($document->file_path, $document->original_name);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $businessId = $contract->contractable_id;
        $this->contracts->destroy($contract);

        return redirect()
            ->route('businesses.contracts.index', ['business_id' => $businessId])
            ->with('success', 'Sözleşme silindi.');
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
