<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessOverviewStats;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Data\BusinessContractDummyData;
use App\Modules\Business\Data\BusinessDocumentDummyData;
use App\Modules\Business\Data\BusinessDummyData;
use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\UpdateBusinessRequest;
use App\Modules\Business\Services\BusinessMediaService;
use App\Modules\Business\Services\BusinessProfileStore;
use App\Support\RequestFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessController extends Controller
{
  use DownloadsListExport;

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

    $all = BusinessDummyData::filter($filters);
    $total = count($all);
    $items = collect(array_slice($all, ($page - 1) * $perPage, $perPage))
      ->map(fn (array $business) => BusinessDummyData::indexRow($business))
      ->all();
    $lastPage = max(1, (int) ceil($total / $perPage));

    return view('modules.business.index', [
      'businesses' => $items,
      'businessesForModal' => collect($items)
        ->mapWithKeys(fn (array $business) => [$business['id'] => BusinessDummyData::detailPayload($business)])
        ->all(),
      'filters' => $filters,
      'cities' => BusinessDummyData::cities(),
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

  public function show(Request $request, int $id): View
  {
    $business = BusinessDummyData::showPayload($id);

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
      'business' => $business,
      'overviewStats' => $overviewStats,
      'dateFilters' => [
        'start_date' => $dateRange['start_date'],
        'end_date' => $dateRange['end_date'],
        'range_label' => $dateRange['range_label'],
      ],
      'contactTitles' => BusinessContactDummyData::titles(),
      'contractTypes' => BusinessContractDummyData::contractTypes(),
      'documentTypes' => BusinessDocumentDummyData::documentTypes(),
      'assignmentCouriers' => BusinessAssignmentDummyData::couriers(),
      'assignmentAgencies' => BusinessAssignmentDummyData::agencies(),
    ]);
  }

  public function edit(int $id): View
  {
    $business = BusinessDummyData::showPayload($id);
    $formValues = BusinessDummyData::formPayload($id);

    if ($business === null || $formValues === null) {
      abort(404);
    }

    return view('modules.business.edit', [
      'business' => $business,
      'formValues' => $formValues,
      'cities' => BusinessFormData::cities(),
      'districtsByCity' => BusinessFormData::districtsByCity(),
      'pricingModels' => BusinessFormData::pricingModels(),
      'pricingFieldLabels' => BusinessFormData::pricingFieldLabels(),
      'earningPeriods' => BusinessFormData::earningPeriods(),
      'statuses' => BusinessFormData::statuses(),
    ]);
  }

  public function update(UpdateBusinessRequest $request, int $id, BusinessMediaService $media): RedirectResponse
  {
    if (! BusinessDummyData::exists($id)) {
      abort(404);
    }

    $data = $request->validated();
    unset($data['logo']);

    if ($request->hasFile('logo')) {
      $stored = BusinessProfileStore::get($id);

      if (! empty($stored['logo_path'])) {
        $media->delete($stored['logo_path']);
      }

      $uploaded = $media->storeLogo($request->file('logo'), $id);
      $data['logo_path'] = $uploaded['path'];
      $data['logo_url'] = $uploaded['url'];
    }

    BusinessProfileStore::put($id, $data);

    return redirect()
      ->route('businesses.show', $id)
      ->with('success', 'İşletme bilgileri güncellendi.');
  }
}
