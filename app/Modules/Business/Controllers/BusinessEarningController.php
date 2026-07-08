<?php

namespace App\Modules\Business\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Business\Data\BusinessAssignmentDummyData;
use App\Modules\Business\Data\BusinessContactDummyData;
use App\Modules\Business\Data\BusinessEarningDummyData;
use App\Modules\Business\Exports\BusinessListExportSheets;
use App\Modules\Business\Support\BusinessFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessEarningController extends Controller
{
    use DownloadsListExport;

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

        $all = BusinessEarningDummyData::filter($filters);
        $summary = BusinessEarningDummyData::summarize($all);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.business.earnings.index', [
            'earnings' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'businesses' => BusinessContactDummyData::businesses(),
            'couriers' => BusinessAssignmentDummyData::couriers(),
            'agencies' => BusinessAssignmentDummyData::agencies(),
            'months' => BusinessEarningDummyData::months(),
            'statuses' => BusinessEarningDummyData::statuses(),
            'pricingModels' => BusinessEarningDummyData::pricingModels(),
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

        $earning = BusinessEarningDummyData::find($id);

        abort_if($earning === null, 404);

        return view('modules.business.earnings.show', [
            'earning' => $earning,
        ]);
    }
}
