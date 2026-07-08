<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessContactFormData;
use App\Modules\Business\Data\BusinessContractDummyData;
use App\Modules\Business\Data\BusinessDocumentDummyData;
use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Data\BusinessOverviewStats;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\StoreBusinessRequest;
use App\Modules\Business\Requests\UpdateBusinessRequest;
use App\Modules\Business\Services\BusinessAssignmentService;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Business\Services\BusinessService;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessController extends Controller
{
  use DownloadsListExport;

  public function __construct(
    private readonly BusinessService $businesses,
    private readonly BusinessPresenter $presenter,
    private readonly BusinessAssignmentService $assignments,
  ) {}

  public function index(Request $request): View
  {
    $filters = [
      'search' => $request->string('search')->toString(),
      'status' => RequestFilter::valueOrAll($request, 'status'),
      'city' => RequestFilter::valueOrAll($request, 'city'),
      'pricing_model' => RequestFilter::valueOrAll($request, 'pricing_model'),
    ];

    $perPage = 25;
    $page = max(1, (int) $request->query('page', 1));

    $all = $this->businesses->filter($filters);
    $total = $all->count();
    $items = $all
      ->slice(($page - 1) * $perPage, $perPage)
      ->map(fn ($business) => $this->presenter->indexRow($business))
      ->values()
      ->all();
    $lastPage = max(1, (int) ceil($total / $perPage));

    return view('modules.business.index', [
      'businesses' => $items,
      'businessesForModal' => collect($items)
        ->mapWithKeys(function (array $business) {
          $model = $this->businesses->find((int) $business['id']);

          return $model
            ? [$business['id'] => $this->presenter->detailPayload($model)]
            : [];
        })
        ->all(),
      'filters' => $filters,
      'cities' => $this->businesses->cities(),
      'statuses' => BusinessFormData::statuses(),
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
      'status' => RequestFilter::valueOrAll($request, 'status'),
      'city' => RequestFilter::valueOrAll($request, 'city'),
      'pricing_model' => RequestFilter::valueOrAll($request, 'pricing_model'),
    ];

    return $this->downloadExportSheet(
      'isletmeler',
      BusinessListExportSheets::businesses($filters),
      'İşletmeler',
    );
  }

  public function create(): View
  {
    return view('modules.business.create', [
      'cities' => BusinessFormData::cities(),
      'districtsByCity' => BusinessFormData::districtsByCity(),
      'pricingModels' => BusinessFormData::pricingModels(),
      'pricingFieldLabels' => BusinessFormData::pricingFieldLabels(),
      'earningPeriods' => BusinessFormData::earningPeriods(),
      'statuses' => BusinessFormData::statuses(),
    ]);
  }

  public function store(StoreBusinessRequest $request): RedirectResponse
  {
    $data = $request->validated();

    if ($request->hasFile('logo')) {
      $data['logo'] = $request->file('logo');
    }

    $business = $this->businesses->create($data, $request->user());

    return redirect()
      ->route('businesses.show', $business->id)
      ->with('success', 'İşletme başarıyla oluşturuldu.');
  }

  public function show(Request $request, int $id): View
  {
    $business = $this->businesses->find($id);

    if ($business === null) {
      abort(404);
    }

    $dateRange = BusinessOverviewStats::resolveDateRange(
      $request->string('start_date')->toString() ?: null,
      $request->string('end_date')->toString() ?: null,
    );

    $overviewStats = BusinessOverviewStats::forBusiness(
      $id,
      $dateRange['start'],
      $dateRange['end'],
    );

    return view('modules.business.show', [
      'business' => $this->presenter->showPayload($business),
      'overviewStats' => $overviewStats,
      'dateFilters' => [
        'start_date' => $dateRange['start_date'],
        'end_date' => $dateRange['end_date'],
        'range_label' => $dateRange['range_label'],
      ],
      'contactTitles' => BusinessContactFormData::titles(),
      'contractTypes' => BusinessContractDummyData::contractTypes(),
      'documentTypes' => BusinessDocumentDummyData::documentTypes(),
      'assignmentCouriers' => $this->assignments->couriers(),
      'assignmentAgencies' => $this->assignments->agencies(),
    ]);
  }

  public function edit(int $id): View
  {
    $business = $this->businesses->find($id);

    if ($business === null) {
      abort(404);
    }

    return view('modules.business.edit', [
      'business' => $this->presenter->showPayload($business),
      'formValues' => $this->presenter->formPayload($business),
      'cities' => BusinessFormData::cities(),
      'districtsByCity' => BusinessFormData::districtsByCity(),
      'pricingModels' => BusinessFormData::pricingModels(),
      'pricingFieldLabels' => BusinessFormData::pricingFieldLabels(),
      'earningPeriods' => BusinessFormData::earningPeriods(),
      'statuses' => BusinessFormData::statuses(),
    ]);
  }

  public function update(UpdateBusinessRequest $request, int $id): RedirectResponse
  {
    $business = $this->businesses->find($id);

    if ($business === null) {
      abort(404);
    }

    $data = $request->validated();

    if ($request->hasFile('logo')) {
      $data['logo'] = $request->file('logo');
    }

    $this->businesses->update($business, $data, $request->user());

    return redirect()
      ->route('businesses.show', $id)
      ->with('success', 'İşletme bilgileri güncellendi.');
  }
}
