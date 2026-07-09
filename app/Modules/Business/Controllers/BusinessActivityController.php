<?php

namespace App\Modules\Business\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessActivityFormData;
use App\Modules\Business\Services\BusinessActivityPresenter;
use App\Modules\Business\Services\BusinessActivityService;
use App\Support\RequestFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessActivityController extends Controller
{
    public function __construct(
        private readonly BusinessActivityService $activities,
        private readonly BusinessActivityPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'business_id' => RequestFilter::valueOrAll($request, 'business_id'),
            'user_id' => RequestFilter::valueOrAll($request, 'user_id'),
            'action' => RequestFilter::valueOrAll($request, 'action'),
            'date_range' => RequestFilter::valueOrAll($request, 'date_range'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->activities->filter($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($log) => $this->presenter->indexRow($log))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.activities.index', [
            'activities' => $items,
            'filters' => $filters,
            'businesses' => $this->activities->businesses(),
            'users' => $this->activities->users(),
            'actionTypes' => BusinessActivityFormData::actionTypes(),
            'dateRanges' => BusinessActivityFormData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }
}
