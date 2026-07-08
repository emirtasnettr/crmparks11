<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessContractDummyData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessContractController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'contract_type' => $request->string('contract_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'end_date' => $request->string('end_date')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = BusinessContractDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.contracts.index', [
            'contracts' => $items,
            'filters' => $filters,
            'businesses' => BusinessContractDummyData::businesses(),
            'contractTypes' => BusinessContractDummyData::contractTypes(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
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
        $contract = BusinessContractDummyData::find($id);

        abort_if($contract === null, 404);

        return view('modules.business.contracts.show', [
            'contract' => $contract,
        ]);
    }
}
