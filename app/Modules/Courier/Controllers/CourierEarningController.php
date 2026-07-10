<?php

namespace App\Modules\Courier\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Requests\ImportBusinessEarningRequest;
use App\Modules\Business\Services\BusinessEarningImportService;
use App\Modules\Courier\Data\CourierEarningFormData;
use App\Modules\Courier\Exports\CourierListExportSheets;
use App\Modules\Courier\Services\CourierEarningPresenter;
use App\Modules\Courier\Services\CourierEarningService;
use App\Modules\Courier\Support\CourierFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourierEarningController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly CourierEarningService $earnings,
        private readonly CourierEarningPresenter $presenter,
        private readonly BusinessEarningImportService $importer,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! CourierFeatures::earningsEnabled()) {
            return redirect()->route('couriers.index');
        }

        $filters = [
            'search' => $request->string('search')->toString(),
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
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

        return view('modules.courier.earnings.index', [
            'earnings' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => $this->earnings->couriers(),
            'businesses' => $this->earnings->businesses(),
            'agencies' => $this->earnings->agencies(),
            'months' => CourierEarningFormData::months(),
            'paymentStatuses' => CourierEarningFormData::paymentStatuses(),
            'courierTypes' => CourierEarningFormData::courierTypes(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! CourierFeatures::earningsEnabled()) {
            return redirect()->route('couriers.index');
        }

        $filters = [
            'search' => $request->string('search')->toString(),
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
            'courier_type' => $request->string('courier_type')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'kurye-hakedisleri',
            CourierListExportSheets::earnings($filters),
            'Kurye Hakedişleri',
        );
    }

    public function template(): BinaryFileResponse|RedirectResponse
    {
        if (! CourierFeatures::earningsEnabled()) {
            return redirect()->route('couriers.index');
        }

        abort_unless(auth()->user()?->can('earning.create'), 403);

        return $this->downloadExportSheet(
            'hakedis-sablonu',
            $this->importer->templateSheet(),
            'Hakediş Şablonu',
        );
    }

    public function import(ImportBusinessEarningRequest $request): RedirectResponse
    {
        if (! CourierFeatures::earningsEnabled()) {
            abort(404);
        }

        $result = $this->importer->import($request->file('file'), $request->user());

        $message = "{$result['imported']} hakediş içe aktarıldı.";

        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} satır atlandı.";
        }

        return redirect()
            ->route('couriers.earnings.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! CourierFeatures::earningsEnabled()) {
            return redirect()->route('couriers.index');
        }

        $earning = $this->earnings->find($id);

        abort_if($earning === null, 404);

        return view('modules.courier.earnings.show', [
            'earning' => $this->presenter->showRow($earning),
        ]);
    }
}
