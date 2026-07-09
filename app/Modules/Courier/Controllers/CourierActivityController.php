<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierActivityFormData;
use App\Modules\Courier\Services\CourierActivityPresenter;
use App\Modules\Courier\Services\CourierActivityService;
use App\Support\RequestFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierActivityController extends Controller
{
    public function __construct(
        private readonly CourierActivityService $activities,
        private readonly CourierActivityPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'courier_id' => RequestFilter::valueOrAll($request, 'courier_id'),
            'action' => RequestFilter::valueOrAll($request, 'action'),
            'user_id' => RequestFilter::valueOrAll($request, 'user_id'),
            'date_range' => RequestFilter::valueOrAll($request, 'date_range'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->activities->filter($filters);
        $summary = $this->activities->summary();
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($log) => $this->presenter->indexRow($log))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.activities.index', [
            'activities' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => $this->activities->couriers(),
            'users' => $this->activities->users(),
            'actionTypes' => CourierActivityFormData::actionTypes(),
            'dateRanges' => CourierActivityFormData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }
}
