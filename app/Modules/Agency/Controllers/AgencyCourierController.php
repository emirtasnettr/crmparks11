<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyCourierFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Requests\StoreAgencyCourierAssignmentRequest;
use App\Modules\Agency\Services\AgencyCourierService;
use App\Support\EntityCardRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyCourierController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly AgencyCourierService $couriers,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'vehicle_type' => $request->string('vehicle_type')->toString() ?: 'all',
            'active_business' => $request->string('active_business')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->couriers->filter($filters);
        $total = $all->count();
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values()->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.couriers.index', [
            'records' => $items,
            'filters' => $filters,
            'agencies' => $this->couriers->agencies(),
            'couriers' => $this->couriers->couriers(),
            'vehicleTypes' => AgencyCourierFormData::vehicleTypes(),
            'businesses' => $this->couriers->activeBusinesses(),
            'summary' => $this->couriers->summarize($filters),
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
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'vehicle_type' => $request->string('vehicle_type')->toString() ?: 'all',
            'active_business' => $request->string('active_business')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-kuryeleri',
            AgencyListExportSheets::couriers($filters),
            'Acente Kuryeleri',
        );
    }

    public function store(StoreAgencyCourierAssignmentRequest $request): RedirectResponse
    {
        $courier = $this->couriers->assign($request->validated(), $request->user());

        if ($request->boolean('redirect_to_agency')) {
            return EntityCardRedirect::toShow(
                route('agencies.show', $courier->agency_id),
                'couriers',
                'Kurye acenteye başarıyla atandı.',
            );
        }

        return redirect()
            ->route('agencies.couriers.index', ['agency_id' => $courier->agency_id])
            ->with('success', 'Kurye acenteye başarıyla atandı.');
    }

    public function detach(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('agency.update'), 403);

        $courier = $this->couriers->find($id);
        abort_if($courier === null || $courier->agency_id === null, 404);

        $agencyId = $courier->agency_id;
        $this->couriers->detach($courier, $request->user());

        return EntityCardRedirect::after(
            route('agencies.couriers.index', ['agency_id' => $agencyId]),
            'Kurye acenteden ayrıldı.',
            route('agencies.show', $agencyId),
            'couriers',
        );
    }
}
