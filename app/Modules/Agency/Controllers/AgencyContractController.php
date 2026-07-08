<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyContractDummyData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyContractController extends Controller
{
    use DownloadsListExport;

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

        $all = AgencyContractDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.contracts.index', [
            'contracts' => $items,
            'filters' => $filters,
            'agencies' => AgencyContractDummyData::agencies(),
            'contractTypes' => AgencyContractDummyData::contractTypes(),
            'startDateFilters' => AgencyContractDummyData::startDateFilters(),
            'endDateFilters' => AgencyContractDummyData::endDateFilters(),
            'summary' => AgencyContractDummyData::summarize($filters),
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
        $contract = AgencyContractDummyData::find($id);

        abort_if($contract === null, 404);

        return view('modules.agency.contracts.show', [
            'contract' => $contract,
        ]);
    }
}
