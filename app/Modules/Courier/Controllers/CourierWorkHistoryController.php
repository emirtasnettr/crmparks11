<?php

namespace App\Modules\Courier\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierWorkHistoryFormData;
use App\Modules\Courier\Services\CourierWorkHistoryPresenter;
use App\Modules\Courier\Services\CourierWorkHistoryService;
use App\Support\EntityCardRedirect;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourierWorkHistoryController extends Controller
{
    public function __construct(
        private readonly CourierWorkHistoryService $workHistory,
        private readonly CourierWorkHistoryPresenter $presenter,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'courier_id' => RequestFilter::valueOrAll($request, 'courier_id'),
            'business_id' => RequestFilter::valueOrAll($request, 'business_id'),
            'agency_id' => RequestFilter::valueOrAll($request, 'agency_id'),
            'courier_type' => RequestFilter::valueOrAll($request, 'courier_type'),
            'status' => RequestFilter::valueOrAll($request, 'status'),
            'date_range' => RequestFilter::valueOrAll($request, 'date_range'),
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->workHistory->filter($filters);
        $summary = $this->workHistory->summarize($filters);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($assignment) => $this->presenter->indexRow($assignment))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.work-history.index', [
            'records' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'businesses' => $this->workHistory->businesses(),
            'agencies' => $this->workHistory->agencies(),
            'statuses' => CourierWorkHistoryFormData::statuses(),
            'courierTypes' => CourierWorkHistoryFormData::courierTypes(),
            'dateRanges' => CourierWorkHistoryFormData::dateRanges(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(int $id): View
    {
        $record = $this->workHistory->find($id);

        abort_if($record === null, 404);

        return view('modules.courier.work-history.show', [
            'record' => $this->presenter->showRow($record),
        ]);
    }

    public function terminate(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->can('courier.update'), 403);

        $record = $this->workHistory->find($id);
        abort_if($record === null, 404);

        $this->workHistory->terminate($record);

        return EntityCardRedirect::after(
            route('couriers.work-history.index', ['courier_id' => $record->courier_id]),
            'Çalışma kaydı sonlandırıldı.',
            route('couriers.show', $record->courier_id),
            'work_history',
        );
    }
}
