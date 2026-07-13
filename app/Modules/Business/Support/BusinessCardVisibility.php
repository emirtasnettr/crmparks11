<?php

namespace App\Modules\Business\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BusinessCardVisibility
{
    /**
     * Operasyon Uzmanı işletme kartında yalnızca atama yönetir;
     * yetkili, sözleşme, evrak ve hareket geçmişini görmez.
     */
    public static function canViewRestrictedTabs(?User $user = null): bool
    {
        $user ??= Auth::user();

        if ($user === null) {
            return false;
        }

        return ! $user->hasRole('operations_specialist');
    }

    /**
     * İşletme listesini görebilir (Operasyon Uzmanı dahil).
     */
    public static function canBrowseBusinesses(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user?->can('business.view') ?? false;
    }

    public static function canManageBusinessProfile(?User $user = null): bool
    {
        $user ??= Auth::user();

        return ($user?->can('business.update') ?? false)
            && self::canViewRestrictedTabs($user);
    }
}
