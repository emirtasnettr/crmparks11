<?php

namespace App\Modules\Business\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BusinessPricingVisibility
{
    /**
     * Operasyon Uzmanı işletmeden alınan tutarı ve net kazancı görmez;
     * yalnızca kuryeye verilen birim ücreti görür.
     */
    public static function canViewCustomerAndNetPricing(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return ! $user->hasRole('operations_specialist');
    }
}
