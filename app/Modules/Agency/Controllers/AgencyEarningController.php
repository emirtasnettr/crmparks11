<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Agency\Data\AgencyEarningDummyData;
use App\Modules\Agency\Exports\AgencyListExportSheets;
use App\Modules\Agency\Support\AgencyFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgencyEarningController extends Controller
{
    use DownloadsListExport;

    public function index(Request $request): View|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        $all = AgencyEarningDummyData::filter($filters);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.agency.earnings.index', [
            'earnings' => $items,
            'filters' => $filters,
            'agencies' => AgencyEarningDummyData::agencies(),
            'months' => AgencyEarningDummyData::months(),
            'earningStatuses' => AgencyEarningDummyData::earningStatuses(),
            'paymentStatuses' => AgencyEarningDummyData::paymentStatuses(),
            'summary' => AgencyEarningDummyData::summarize($filters),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $filters = [
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'period_month' => $request->string('period_month')->toString() ?: 'all',
            'period_year' => $request->string('period_year')->toString() ?: 'all',
            'status' => $request->string('status')->toString() ?: 'all',
            'payment_status' => $request->string('payment_status')->toString() ?: 'all',
        ];

        return $this->downloadExportSheet(
            'acente-hakedisleri',
            AgencyListExportSheets::earnings($filters),
            'Acente Hakedişleri',
        );
    }

    public function show(int $id): View|RedirectResponse
    {
        if (! AgencyFeatures::earningsEnabled()) {
            return redirect()->route('agencies.index');
        }

        $earning = AgencyEarningDummyData::find($id);

        abort_if($earning === null, 404);

        return view('modules.agency.earnings.show', [
            'earning' => $earning,
        ]);
    }
}
