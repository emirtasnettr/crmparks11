<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyContactDummyData;
use App\Modules\Agency\Data\AgencyContractDummyData;
use App\Modules\Agency\Data\AgencyCourierDummyData;
use App\Modules\Agency\Data\AgencyDocumentDummyData;
use App\Modules\Agency\Data\AgencyFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Requests\StoreAgencyRequest;
use App\Modules\Agency\Requests\UpdateAgencyRequest;
use App\Modules\Agency\Services\AgencyPresenter;
use App\Modules\Agency\Services\AgencyService;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly AgencyService $agencies,
        private readonly AgencyPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'city' => RequestFilter::valueOrAll($request, 'city'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
            'courier_count' => RequestFilter::valueOrAll($request, 'courier_count'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->agencies->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($agency) => $this->presenter->indexRow($agency))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.index', [
            'agencies' => $items,
            'agenciesForModal' => collect($items)
                ->mapWithKeys(function (array $agency) {
                    $model = $this->agencies->find((int) $agency['id']);

                    return $model
                        ? [$agency['id'] => $this->presenter->detailPayload($model)]
                        : [];
                })
                ->all(),
            'filters' => $filters,
            'cities' => $this->agencies->cities(),
            'statuses' => AgencyFormData::statuses(),
            'courierCountRanges' => AgencyFormData::courierCountRanges(),
            'summary' => $this->agencies->summary($filters),
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
            'city' => RequestFilter::valueOrAll($request, 'city'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
            'courier_count' => RequestFilter::valueOrAll($request, 'courier_count'),
        ];

        return $this->downloadExportSheet(
            'acenteler',
            AgencyListExportSheets::agencies($filters),
            'Acenteler',
        );
    }

    public function create(): View
    {
        return view('modules.agency.create', [
            'cities' => AgencyFormData::cities(),
            'districtsByCity' => AgencyFormData::districtsByCity(),
            'statuses' => AgencyFormData::statuses(),
            'paymentPeriods' => AgencyFormData::paymentPeriods(),
            'banks' => AgencyFormData::banks(),
        ]);
    }

    public function store(StoreAgencyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $agency = $this->agencies->create($data, $request->user());

        return redirect()
            ->route('agencies.show', $agency->id)
            ->with('success', 'Acente başarıyla oluşturuldu.');
    }

    public function show(int $id): View
    {
        $agency = $this->agencies->find($id);

        if ($agency === null) {
            abort(404);
        }

        return view('modules.agency.show', [
            'agency' => $this->presenter->showPayload($agency),
            'contactTitles' => AgencyContactDummyData::titles(),
            'contractTypes' => AgencyContractDummyData::contractTypes(),
            'documentTypes' => AgencyDocumentDummyData::documentTypes(),
            'assignCouriers' => AgencyCourierDummyData::couriers(),
        ]);
    }

    public function edit(int $id): View
    {
        $agency = $this->agencies->find($id);

        if ($agency === null) {
            abort(404);
        }

        return view('modules.agency.edit', [
            'agency' => $this->presenter->showPayload($agency),
            'formValues' => $this->presenter->formPayload($agency),
            'cities' => AgencyFormData::cities(),
            'districtsByCity' => AgencyFormData::districtsByCity(),
            'statuses' => AgencyFormData::statuses(),
            'paymentPeriods' => AgencyFormData::paymentPeriods(),
            'banks' => AgencyFormData::banks(),
        ]);
    }

    public function update(UpdateAgencyRequest $request, int $id): RedirectResponse
    {
        $agency = $this->agencies->find($id);

        if ($agency === null) {
            abort(404);
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo');
        }

        $this->agencies->update($agency, $data, $request->user());

        return redirect()
            ->route('agencies.show', $id)
            ->with('success', 'Acente bilgileri güncellendi.');
    }
}
