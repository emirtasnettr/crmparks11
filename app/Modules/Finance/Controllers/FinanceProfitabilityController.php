<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Http\Concerns\DownloadsListExport;
use App\Http\Controllers\Controller;
use App\Modules\Finance\Data\ProfitabilityFormData;
use App\Modules\Finance\Exports\FinanceListExportSheets;
use App\Modules\Finance\Services\ProfitabilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceProfitabilityController extends Controller
{
    use DownloadsListExport;

    public function __construct(
        private readonly ProfitabilityService $profitabilityService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'date_range' => $request->string('date_range')->toString() ?: 'month',
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'city' => $request->string('city')->toString() ?: 'all',
            'pricing_model' => $request->string('pricing_model')->toString() ?: 'all',
            'profit_margin' => $request->string('profit_margin')->toString() ?: 'all',
        ];

        $analysis = $this->profitabilityService->analyze($filters);

        return view('modules.finance.profitability.index', array_merge($analysis, [
            'filters' => $filters,
            'dateRanges' => ProfitabilityFormData::dateRanges(),
            'businesses' => $this->profitabilityService->businesses(),
            'couriers' => $this->profitabilityService->couriers(),
            'agencies' => $this->profitabilityService->agencies(),
            'cities' => $this->profitabilityService->cities(),
            'pricingModels' => ProfitabilityFormData::pricingModels(),
            'profitMarginFilters' => ProfitabilityFormData::profitMarginFilters(),
        ]));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'date_range' => $request->string('date_range')->toString() ?: 'month',
            'business_id' => $request->string('business_id')->toString() ?: 'all',
            'courier_id' => $request->string('courier_id')->toString() ?: 'all',
            'agency_id' => $request->string('agency_id')->toString() ?: 'all',
            'city' => $request->string('city')->toString() ?: 'all',
            'pricing_model' => $request->string('pricing_model')->toString() ?: 'all',
            'profit_margin' => $request->string('profit_margin')->toString() ?: 'all',
        ];

        return $this->downloadMultipleExportSheets(
            'karlilik-analizi',
            FinanceListExportSheets::profitability($filters),
        );
    }
}
