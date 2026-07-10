<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessEarningFormData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Requests\ApproveBusinessEarningRequest;
use App\Modules\Business\Requests\StoreBusinessEarningRequest;
use App\Modules\Business\Requests\UpdateBusinessEarningRequest;
use App\Modules\Business\Services\BusinessEarningPresenter;
use App\Modules\Business\Services\BusinessEarningService;
use App\Modules\Business\Support\BusinessFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessEarningController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly BusinessEarningService $earnings,
        private readonly BusinessEarningPresenter $presenter,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            return redirect()->route('businesses.index');
        }

        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'pricing_model' => $request->string('pricing_model')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = $this->earnings->filter($filters);
        $summary = $this->earnings->summarize($all);
        $total = $all->count();
        $items = $all
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($line) => $this->presenter->indexRow($line))
            ->values()
            ->all();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.earnings.index', [
            'earnings' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'businesses' => $this->earnings->businesses(),
            'couriers' => $this->earnings->couriers(),
            'agencies' => $this->earnings->agencies(),
            'months' => BusinessEarningFormData::months(),
            'statuses' => BusinessEarningFormData::statuses(),
            'pricingModels' => BusinessEarningFormData::pricingModels(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            return redirect()->route('businesses.index');
        }

        $filters = [
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'pricing_model' => $request->string('pricing_model')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'isletme-hakedisleri',
            BusinessListExportSheets::earnings($filters),
            'İşletme Hakedişleri',
        );
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            return redirect()->route('businesses.index');
        }

        $earning = $this->earnings->find($id);

        abort_if($earning === null, 404);

        return view('modules.business.earnings.show', [
            'earning' => $this->presenter->showRow($earning),
            'businesses' => $this->earnings->businesses(),
            'couriers' => $this->earnings->couriers(),
            'months' => BusinessEarningFormData::months(),
            'pricingModels' => BusinessEarningFormData::pricingModels(),
        ]);
    }

    public function store(StoreBusinessEarningRequest $request): RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            abort(404);
        }

        $line = $this->earnings->create($request->validated(), $request->user());

        return redirect()
            ->route('businesses.earnings.index', [
                'business_id' => $line->business_id,
                'period_month' => $line->period_month,
                'period_year' => $line->period_year,
            ])
            ->with('success', 'Hakediş başarıyla oluşturuldu.');
    }

    public function update(UpdateBusinessEarningRequest $request, int $id): RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            abort(404);
        }

        $line = $this->earnings->update($id, $request->validated(), $request->user());

        return redirect()
            ->route('businesses.earnings.show', $line->id)
            ->with('success', 'Hakediş başarıyla güncellendi.');
    }

    public function approve(ApproveBusinessEarningRequest $request, int $id): RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            abort(404);
        }

        $line = $this->earnings->approve($id, $request->user());

        return redirect()
            ->route('businesses.earnings.show', $line->id)
            ->with('success', 'Hakediş onaylandı.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if (! BusinessFeatures::earningsEnabled()) {
            abort(404);
        }

        abort_unless($request->user()?->can('earning.delete'), 403);

        $line = $this->earnings->find($id);
        $filters = $line ? [
            'business_id' => $line->business_id,
            'period_month' => $line->period_month,
            'period_year' => $line->period_year,
        ] : [];

        $this->earnings->delete($id, $request->user());

        return redirect()
            ->route('businesses.earnings.index', $filters)
            ->with('success', 'Hakediş silindi.');
    }
}
