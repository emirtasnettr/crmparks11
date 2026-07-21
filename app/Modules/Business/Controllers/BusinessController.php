<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Neighborhood;
use App\Modules\Business\Data\BusinessCommercialContractFormData;
use App\Modules\Business\Data\BusinessContactFormData;
use App\Modules\Business\Data\BusinessContractFormData;
use App\Modules\Business\Data\BusinessDocumentFormData;
use App\Modules\Business\Data\BusinessFormData;
use App\Modules\Business\Data\BusinessOverviewStats;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\StoreBusinessRequest;
use App\Modules\Business\Requests\UpdateBusinessRequest;
use App\Modules\Business\Services\BusinessGeocodeService;
use App\Modules\Business\Services\BusinessPresenter;
use App\Modules\Business\Services\BusinessService;
use App\Support\RequestFilter;
use Illuminate\Http\JsonResponse;
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
    private readonly BusinessGeocodeService $geocoder,
  ) {}

  public function index(Request $request): View
  {
    abort_unless(\App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses($request->user()), 403);

    $filters = [
      'search' => $request->string('search')->toString(),
      'status' => RequestFilter::valueOrAll($request, 'status'),
      'city' => RequestFilter::valueOrAll($request, 'city'),
      'work_type' => RequestFilter::valueOrAll($request, 'work_type'),
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
      'workTypes' => BusinessCommercialContractFormData::workTypes(),
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
      'work_type' => RequestFilter::valueOrAll($request, 'work_type'),
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

    public function show(Request $request, int $id): View|RedirectResponse
    {
        $business = $this->businesses->find($id);

        if ($business === null) {
            abort(404);
        }

        if ($request->user()?->hasRole('operations_specialist')) {
            $tab = $request->string('tab')->toString();

            if (in_array($tab, ['contacts', 'contracts', 'commercial-contracts', 'documents', 'activities'], true)) {
                return redirect()->route('businesses.show', ['id' => $id, 'tab' => 'overview']);
            }
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
            'contractTypes' => BusinessContractFormData::contractTypes(),
            'documentTypes' => BusinessDocumentFormData::documentTypes(),
            'commercialWorkTypes' => BusinessCommercialContractFormData::workTypes(),
            'commercialPaymentPeriods' => BusinessCommercialContractFormData::paymentPeriods(),
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
      'earningPeriods' => BusinessFormData::earningPeriods(),
      'statuses' => BusinessFormData::statuses(),
    ]);
  }

  public function geocode(Request $request): JsonResponse
  {
    abort_unless(
      $request->user()?->can('business.create') || $request->user()?->can('business.update'),
      403
    );

    $data = $request->validate([
      'city' => ['nullable', 'string', 'max:100'],
      'district' => ['nullable', 'string', 'max:100'],
      'neighborhood' => ['nullable', 'string', 'max:150'],
      'address' => ['nullable', 'string', 'max:1000'],
    ]);

    $result = $this->geocoder->locate(
      (string) ($data['city'] ?? ''),
      (string) ($data['district'] ?? ''),
      (string) ($data['neighborhood'] ?? ''),
      (string) ($data['address'] ?? ''),
    );

    if ($result === null) {
      return response()->json([
        'message' => 'Adres haritada bulunamadı. Pin’i elle sürükleyebilirsiniz.',
      ], 422);
    }

    return response()->json($result);
  }

  public function neighborhoods(Request $request): JsonResponse
  {
    abort_unless(
      $request->user()?->can('business.create') || $request->user()?->can('business.update') || $request->user()?->can('business.view'),
      403
    );

    $data = $request->validate([
      'city' => ['required', 'string', 'max:100'],
      'district' => ['required', 'string', 'max:100'],
    ]);

    $city = City::query()->where('name', $data['city'])->first();
    if ($city === null) {
      return response()->json(['neighborhoods' => []]);
    }

    $district = District::query()
      ->where('city_id', $city->id)
      ->where('name', $data['district'])
      ->first();

    if ($district === null) {
      return response()->json(['neighborhoods' => []]);
    }

    $neighborhoods = Neighborhood::query()
      ->where('district_id', $district->id)
      ->orderBy('name')
      ->pluck('name')
      ->values()
      ->all();

    return response()->json(['neighborhoods' => $neighborhoods]);
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

  public function deactivate(Request $request, int $id): RedirectResponse
  {
    abort_unless($request->user()?->can('business.update'), 403);

    $business = $this->businesses->find($id);

    if ($business === null) {
      abort(404);
    }

    $data = $request->validate([
      'contract_end_date' => ['required', 'date'],
      'notes' => ['nullable', 'string', 'max:5000'],
    ], [
      'contract_end_date.required' => 'Pasif durum için sözleşme bitiş tarihi zorunludur.',
    ]);

    $this->businesses->deactivate($business, $data);

    return redirect()
      ->route('businesses.index')
      ->with('success', 'İşletme pasife alındı.');
  }

  public function destroy(Request $request, int $id): RedirectResponse
  {
    abort_unless($request->user()?->hasRole('super_admin'), 403);

    $business = $this->businesses->find($id);

    if ($business === null) {
      abort(404);
    }

    $this->businesses->destroy($business);

    return redirect()
      ->route('businesses.index')
      ->with('success', 'İşletme silindi.');
  }
}
