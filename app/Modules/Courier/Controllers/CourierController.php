<?php

namespace App\Modules\Courier\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierBankAccountFormData;
use App\Modules\Courier\Data\CourierDocumentFormData;
use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Data\CourierVehicleFormData;
use App\Modules\Courier\Exports\CourierListExportSheets;
use App\Modules\Courier\Requests\StoreCourierRequest;
use App\Modules\Courier\Requests\UpdateCourierRequest;
use App\Modules\Courier\Services\CourierPresenter;
use App\Modules\Courier\Services\CourierService;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourierController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly CourierService $couriers,
        private readonly CourierPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'courier_type' => RequestFilter::valueOrAll($request, 'courier_type'),
            'agency_id' => RequestFilter::valueOrAll($request, 'agency_id'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
            'vehicle_type' => RequestFilter::valueOrAll($request, 'vehicle_type'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->couriers->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($courier) => $this->presenter->indexRow($courier))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.index', [
            'couriers' => $items,
            'couriersForModal' => collect($items)
                ->mapWithKeys(function (array $courier) {
                    $model = $this->couriers->find((int) $courier['id']);

                    return $model
                        ? [$courier['id'] => $this->presenter->detailPayload($model)]
                        : [];
                })
                ->all(),
            'filters' => $filters,
            'agencies' => $this->couriers->agencyOptions(),
            'vehicleTypes' => CourierFormData::vehicleTypes(),
            'courierTypes' => CourierFormData::courierTypes(),
            'statuses' => CourierFormData::statuses(),
            'summary' => $this->couriers->summary($filters),
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
            'courier_type' => RequestFilter::valueOrAll($request, 'courier_type'),
            'agency_id' => RequestFilter::valueOrAll($request, 'agency_id'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
            'vehicle_type' => RequestFilter::valueOrAll($request, 'vehicle_type'),
        ];

        return $this->downloadExportSheet(
            'kuryeler',
            CourierListExportSheets::couriers($filters),
            'Kuryeler',
        );
    }

    public function create(): View
    {
        return view('modules.courier.create', [
            'cities' => CourierFormData::cities(),
            'districtsByCity' => CourierFormData::districtsByCity(),
            'courierTypes' => CourierFormData::courierTypes(),
            'agencies' => CourierFormData::agencies(),
            'vehicleTypes' => CourierFormData::vehicleTypes(),
            'statuses' => CourierFormData::statuses(),
            'banks' => CourierFormData::banks(),
        ]);
    }

    public function store(StoreCourierRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $courier = $this->couriers->create($data, $request->user());

        return redirect()
            ->route('couriers.show', $courier->id)
            ->with('success', 'Kurye başarıyla oluşturuldu.');
    }

    public function show(int $id): View
    {
        $courier = $this->couriers->find($id);

        if ($courier === null) {
            abort(404);
        }

        return view('modules.courier.show', [
            'courier' => $this->presenter->showPayload($courier),
            'documentTypes' => CourierDocumentFormData::documentTypes(),
            'banks' => CourierBankAccountFormData::banks(),
            'bankStatuses' => CourierBankAccountFormData::statuses(),
            'vehicleTypes' => CourierVehicleFormData::vehicleTypes(),
            'vehicleStatuses' => CourierVehicleFormData::statuses(),
        ]);
    }

    public function edit(int $id): View
    {
        $courier = $this->couriers->find($id);

        if ($courier === null) {
            abort(404);
        }

        return view('modules.courier.edit', [
            'courier' => $this->presenter->showPayload($courier),
            'formValues' => $this->presenter->formPayload($courier),
            'cities' => CourierFormData::cities(),
            'districtsByCity' => CourierFormData::districtsByCity(),
            'courierTypes' => CourierFormData::courierTypes(),
            'agencies' => CourierFormData::agencies(),
            'vehicleTypes' => CourierFormData::vehicleTypes(),
            'statuses' => CourierFormData::statuses(),
            'banks' => CourierFormData::banks(),
        ]);
    }

    public function update(UpdateCourierRequest $request, int $id): RedirectResponse
    {
        $courier = $this->couriers->find($id);

        if ($courier === null) {
            abort(404);
        }

        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo');
        }

        $this->couriers->update($courier, $data, $request->user());

        return redirect()
            ->route('couriers.show', $id)
            ->with('success', 'Kurye bilgileri güncellendi.');
    }
}
