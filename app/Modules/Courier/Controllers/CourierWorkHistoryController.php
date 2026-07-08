<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierWorkHistoryDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierWorkHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = CourierWorkHistoryDummyData::filter($filters);
        $summary = CourierWorkHistoryDummyData::summarize($all);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.work-history.index', [
            'records' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'businesses' => CourierWorkHistoryDummyData::businesses(),
            'agencies' => CourierWorkHistoryDummyData::agencies(),
            'statuses' => CourierWorkHistoryDummyData::statuses(),
            'courierTypes' => CourierWorkHistoryDummyData::courierTypes(),
            'dateRanges' => CourierWorkHistoryDummyData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $record = CourierWorkHistoryDummyData::find($id);

        abort_if($record === null, 404);

        return view('modules.courier.work-history.show', [
            'record' => $record,
        ]);
    }
}
