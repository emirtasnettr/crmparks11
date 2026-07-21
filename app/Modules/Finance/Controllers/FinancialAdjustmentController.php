<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Business\Models\Business;
use App\Modules\Courier\Models\Courier;
use App\Modules\Finance\Requests\StoreFinancialAdjustmentRequest;
use App\Modules\Finance\Services\FinancialAdjustmentService;
use Illuminate\Http\RedirectResponse;

class FinancialAdjustmentController extends Controller
{
    public function __construct(
        private readonly FinancialAdjustmentService $adjustments,
    ) {}

    public function storeForCourier(StoreFinancialAdjustmentRequest $request, int $id): RedirectResponse
    {
        $courier = Courier::query()->findOrFail($id);

        $this->adjustments->adjustCourier(
            $courier,
            $request->validated(),
            $request->user(),
        );

        return back()->with('success', 'Tutar düzeltmesi kaydedildi.');
    }

    public function storeForBusiness(StoreFinancialAdjustmentRequest $request, int $id): RedirectResponse
    {
        $business = Business::query()->findOrFail($id);

        $this->adjustments->adjustBusiness(
            $business,
            $request->validated(),
            $request->user(),
        );

        return back()->with('success', 'Tutar düzeltmesi kaydedildi.');
    }
}
