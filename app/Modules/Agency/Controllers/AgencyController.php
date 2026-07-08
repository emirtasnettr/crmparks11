<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyContactDummyData;
use App\Modules\Agency\Data\AgencyContractDummyData;
use App\Modules\Agency\Data\AgencyCourierDummyData;
use App\Modules\Agency\Data\AgencyDocumentDummyData;
use App\Modules\Agency\Data\AgencyDummyData;
use App\Modules\Agency\Data\AgencyFormData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Requests\UpdateAgencyRequest;
use App\Modules\Agency\Services\AgencyMediaService;
use App\Modules\Agency\Services\AgencyProfileStore;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyController extends Controller
{
    use DownloadsListExport;

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

        $all = AgencyDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.index', [
            'agencies' => $items,
            'agenciesForModal' => collect($items)
                ->mapWithKeys(fn (array $agency) => [$agency['id'] => AgencyDummyData::detailPayload($agency)])
                ->all(),
            'filters' => $filters,
            'cities' => AgencyDummyData::cities(),
            'statuses' => AgencyDummyData::statuses(),
            'courierCountRanges' => AgencyDummyData::courierCountRanges(),
            'summary' => AgencyDummyData::summary($filters),
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

    public function show(int $id): View
    {
        $agency = AgencyDummyData::showPayload($id);

        if ($agency === null) {
            abort(404);
        }

        return view('modules.agency.show', [
            'agency' => $agency,
            'contactTitles' => AgencyContactDummyData::titles(),
            'contractTypes' => AgencyContractDummyData::contractTypes(),
            'documentTypes' => AgencyDocumentDummyData::documentTypes(),
            'assignCouriers' => AgencyCourierDummyData::couriers(),
        ]);
    }

    public function edit(int $id): View
    {
        $agency = AgencyDummyData::showPayload($id);
        $formValues = AgencyDummyData::formPayload($id);

        if ($agency === null || $formValues === null) {
            abort(404);
        }

        return view('modules.agency.edit', [
            'agency' => $agency,
            'formValues' => $formValues,
            'cities' => AgencyFormData::cities(),
            'districtsByCity' => AgencyFormData::districtsByCity(),
            'statuses' => AgencyFormData::statuses(),
            'paymentPeriods' => AgencyFormData::paymentPeriods(),
            'banks' => AgencyFormData::banks(),
        ]);
    }

    public function update(UpdateAgencyRequest $request, int $id, AgencyMediaService $media): RedirectResponse
    {
        if (! AgencyDummyData::exists($id)) {
            abort(404);
        }

        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $stored = AgencyProfileStore::get($id);

            if (! empty($stored['logo_path'])) {
                $media->delete($stored['logo_path']);
            }

            $uploaded = $media->storeLogo($request->file('logo'), $id);
            $data['logo_path'] = $uploaded['path'];
            $data['logo_url'] = $uploaded['url'];
        }

        AgencyProfileStore::put($id, $data);

        return redirect()
            ->route('agencies.show', $id)
            ->with('success', 'Acente bilgileri güncellendi.');
    }
}
