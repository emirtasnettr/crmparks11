<?php

namespace App\Modules\Courier\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierBankAccountDummyData;
use App\Modules\Courier\Data\CourierDocumentDummyData;
use App\Modules\Courier\Data\CourierDummyData;
use App\Modules\Courier\Data\CourierVehicleDummyData;
use App\Modules\Courier\Data\CourierFormData;
use App\Modules\Courier\Exports\CourierListExportSheets;
use App\Modules\Courier\Requests\UpdateCourierRequest;
use App\Modules\Courier\Services\CourierMediaService;
use App\Modules\Courier\Services\CourierProfileStore;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourierController extends Controller
{
    use DownloadsListExport;

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

        $all = CourierDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.index', [
            'couriers' => $items,
            'couriersForModal' => collect($items)
                ->mapWithKeys(fn (array $courier) => [$courier['id'] => CourierDummyData::detailPayload($courier)])
                ->all(),
            'filters' => $filters,
            'agencies' => CourierDummyData::agencies(),
            'vehicleTypes' => CourierDummyData::vehicleTypes(),
            'courierTypes' => CourierDummyData::courierTypes(),
            'statuses' => CourierDummyData::statuses(),
            'summary' => CourierDummyData::summary($filters),
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

    public function show(int $id): View
    {
        $courier = CourierDummyData::showPayload($id);

        if ($courier === null) {
            abort(404);
        }

        return view('modules.courier.show', [
            'courier' => $courier,
            'documentTypes' => CourierDocumentDummyData::documentTypes(),
            'banks' => CourierBankAccountDummyData::banks(),
            'bankStatuses' => CourierBankAccountDummyData::statuses(),
            'vehicleTypes' => CourierVehicleDummyData::vehicleTypes(),
            'vehicleStatuses' => CourierVehicleDummyData::statuses(),
        ]);
    }

    public function edit(int $id): View
    {
        $courier = CourierDummyData::showPayload($id);
        $formValues = CourierDummyData::formPayload($id);

        if ($courier === null || $formValues === null) {
            abort(404);
        }

        return view('modules.courier.edit', [
            'courier' => $courier,
            'formValues' => $formValues,
            'cities' => CourierFormData::cities(),
            'districtsByCity' => CourierFormData::districtsByCity(),
            'courierTypes' => CourierFormData::courierTypes(),
            'agencies' => CourierFormData::agencies(),
            'vehicleTypes' => CourierFormData::vehicleTypes(),
            'statuses' => CourierFormData::statuses(),
            'banks' => CourierFormData::banks(),
        ]);
    }

    public function update(UpdateCourierRequest $request, int $id, CourierMediaService $media): RedirectResponse
    {
        if (! CourierDummyData::exists($id)) {
            abort(404);
        }

        $data = $request->validated();
        unset($data['profile_photo']);

        if ($request->hasFile('profile_photo')) {
            $stored = CourierProfileStore::get($id);

            if (! empty($stored['photo_path'])) {
                $media->delete($stored['photo_path']);
            }

            $uploaded = $media->storePhoto($request->file('profile_photo'), $id);
            $data['photo_path'] = $uploaded['path'];
            $data['photo_url'] = $uploaded['url'];
        }

        CourierProfileStore::put($id, $data);

        return redirect()
            ->route('couriers.show', $id)
            ->with('success', 'Kurye bilgileri güncellendi.');
    }
}
