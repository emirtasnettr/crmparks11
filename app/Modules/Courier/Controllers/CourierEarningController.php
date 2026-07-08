<?php

namespace App\Modules\Courier\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Courier\Data\CourierEarningDummyData;
use App\Modules\Courier\Exports\CourierListExportSheets;
use App\Modules\Courier\Support\CourierFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourierEarningController extends Controller
{
    use DownloadsListExport;

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

        $all = CourierEarningDummyData::filter($filters);
        $summary = CourierEarningDummyData::summarize($all);
        $total = count($all);
        $items = array_slice($all, ($page - 1) * $perPage, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));

        return view('modules.courier.earnings.index', [
            'earnings' => $items,
            'filters' => $filters,
            'summary' => $summary,
            'couriers' => CourierEarningDummyData::couriers(),
            'businesses' => CourierEarningDummyData::businesses(),
            'agencies' => CourierEarningDummyData::agencies(),
            'months' => CourierEarningDummyData::months(),
            'paymentStatuses' => CourierEarningDummyData::paymentStatuses(),
            'courierTypes' => CourierEarningDummyData::courierTypes(),
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

    public function show(int $id): View|RedirectResponse
    {
        if (! CourierFeatures::earningsEnabled()) {
            return redirect()->route('couriers.index');
        }

        $earning = CourierEarningDummyData::find($id);

        abort_if($earning === null, 404);

        return view('modules.courier.earnings.show', [
            'earning' => $earning,
        ]);
    }
}
