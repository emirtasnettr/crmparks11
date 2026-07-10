<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyContractFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Requests\StoreAgencyContractRequest;
use App\Modules\Agency\Services\AgencyContractPresenter;
use App\Modules\Agency\Services\AgencyContractService;
use App\Modules\Agency\Services\AgencyDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyContractController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly AgencyContractService $contracts,
        private readonly AgencyContractPresenter $presenter,
        private readonly AgencyDocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'contract_type' => $request->string('contract_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'start_date' => $request->string('start_date')->toString() ?: 'all',
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

        return view('modules.agency.contracts.index', [
            'contracts' => $items,
            'filters' => $filters,
            'agencies' => $this->contracts->agencies(),
            'contractTypes' => AgencyContractFormData::contractTypes(),
            'startDateFilters' => AgencyContractFormData::startDateFilters(),
            'endDateFilters' => AgencyContractFormData::endDateFilters(),
            'summary' => $this->contracts->summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'contract_type' => $request->string('contract_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'start_date' => $request->string('start_date')->toString() ?: 'all',
            'end_date' => $request->string('end_date')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-sozlesmeleri',
            AgencyListExportSheets::contracts($filters),
            'Acente Sözleşmeleri',
        );
    }

    public function show(int $id): View
    {
        $contract = $this->contracts->find($id);

        abort_if($contract === null, 404);

        return view('modules.agency.contracts.show', [
            'contract' => $this->presenter->showRow($contract),
        ]);
    }

    public function store(StoreAgencyContractRequest $request): RedirectResponse
    {
        $contract = $this->contracts->create($request->validated(), $request->user());

        if ($request->hasFile('contract_file')) {
            $agency = Agency::query()->findOrFail($contract->contractable_id);
            $this->documents->create([
                'agency_id' => $agency->id,
                'document_type' => 'contract',
            ], $request->file('contract_file'), $request->user());
        }

        if ($request->boolean('redirect_to_agency')) {
            return redirect()
                ->route('agencies.show', $contract->contractable_id)
                ->with('success', 'Sözleşme başarıyla oluşturuldu.');
        }

        return redirect()
            ->route('agencies.contracts.index', ['agency_id' => $contract->contractable_id])
            ->with('success', 'Sözleşme başarıyla oluşturuldu.');
    }

    public function deactivate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('agency.update'), 403);

        $contract = $this->contracts->find($id);
        abort_if($contract === null, 404);

        $this->contracts->deactivate($contract);

        return redirect()
            ->route('agencies.contracts.index', ['agency_id' => $contract->contractable_id])
            ->with('success', 'Sözleşme pasife alındı.');
    }
}
