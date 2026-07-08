<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessActivityDummyData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessActivityController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'user_id' => $request->string('user_id')->toString() ?: 'all',
            'action' => $request->string('action')->toString() ?: 'all',
            'date_range' => $request->string('date_range')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = BusinessActivityDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.activities.index', [
            'activities' => $items,
            'filters' => $filters,
            'businesses' => BusinessActivityDummyData::businesses(),
            'users' => BusinessActivityDummyData::users(),
            'actionTypes' => BusinessActivityDummyData::actionTypes(),
            'dateRanges' => BusinessActivityDummyData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }
}
