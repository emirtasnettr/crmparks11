<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierActivityDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierActivityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'action' => $request->string('action')->toString() ?: 'all',
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $allRecords = CourierActivityDummyData::all();
        $all = CourierActivityDummyData::filter($filters);
        $summary = CourierActivityDummyData::summarize($allRecords);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.activities.index', [
            'activities' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => CourierActivityDummyData::couriers(),
            'users' => CourierActivityDummyData::users(),
            'actionTypes' => CourierActivityDummyData::actionTypes(),
            'dateRanges' => CourierActivityDummyData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }
}
