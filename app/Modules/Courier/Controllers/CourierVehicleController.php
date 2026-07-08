<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierVehicleDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierVehicleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'vehicle_type' => $request->string('vehicle_type')->toString() ?: 'all',
            'brand' => $request->string('brand')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = CourierVehicleDummyData::filter($filters);
        $summary = CourierVehicleDummyData::summarize(CourierVehicleDummyData::all());
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.vehicles.index', [
            'vehicles' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => CourierVehicleDummyData::couriers(),
            'vehicleTypes' => CourierVehicleDummyData::vehicleTypes(),
            'brands' => CourierVehicleDummyData::brands(),
            'statuses' => CourierVehicleDummyData::statuses(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $vehicle = CourierVehicleDummyData::find($id);

        abort_if($vehicle === null, 404);

        return view('modules.courier.vehicles.show', [
            'vehicle' => $vehicle,
            'courierVehicles' => CourierVehicleDummyData::courierVehicleHistory($vehicle['courier_id']),
        ]);
    }
}
