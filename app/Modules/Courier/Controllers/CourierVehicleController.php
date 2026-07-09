<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierVehicleFormData;
use App\Modules\Courier\Requests\StoreCourierVehicleRequest;
use App\Modules\Courier\Services\CourierVehiclePresenter;
use App\Modules\Courier\Services\CourierVehicleService;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierVehicleController extends Controller
{
    public function __construct(
        private readonly CourierVehicleService $vehicles,
        private readonly CourierVehiclePresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'courier_id' => RequestFilter::valueOrAll($request, 'courier_id'),
            'vehicle_type' => RequestFilter::valueOrAll($request, 'vehicle_type'),
            'brand' => RequestFilter::valueOrAll($request, 'brand'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->vehicles->filter($filters);
        $summary = $this->vehicles->summary();
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($vehicle) => $this->presenter->indexRow($vehicle))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.vehicles.index', [
            'vehicles' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => $this->vehicles->couriers(),
            'vehicleTypes' => CourierVehicleFormData::vehicleTypes(),
            'brands' => $this->vehicles->brands(),
            'statuses' => CourierVehicleFormData::statuses(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $vehicle = $this->vehicles->find($id);

        abort_if($vehicle === null, 404);

        return view('modules.courier.vehicles.show', [
            'vehicle' => $this->presenter->showRow($vehicle),
            'courierVehicles' => $this->vehicles->forCourier($vehicle->courier_id)
                ->map(fn ($item) => $this->presenter->indexRow($item))
                ->values()
                ->all(),
        ]);
    }

    public function store(StoreCourierVehicleRequest $request): RedirectResponse
    {
        $vehicle = $this->vehicles->create($request->validated());

        if ($request->boolean('redirect_to_courier')) {
            return redirect()
                ->route('couriers.show', $vehicle->courier_id)
                ->with('success', 'Araç başarıyla kaydedildi.');
        }

        return redirect()
            ->route('couriers.vehicles.index', ['courier_id' => $vehicle->courier_id])
            ->with('success', 'Araç başarıyla kaydedildi.');
    }
}
