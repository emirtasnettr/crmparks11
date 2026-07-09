<?php

namespace App\Support;

use Carbon\Carbon;

final class ContractStatusResolver
{
    public static function resolve(string $storedStatus, Carbon $startDate, Carbon $endDate, ?Carbon $today = null): string
    {
        $today ??= Carbon::today();

        if ($storedStatus === 'draft') {
            return 'draft';
        }

        if ($endDate->lt($today)) {
            return 'expired';
        }

        if ($today->diffInDays($endDate, false) <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public static function isCurrent(string $displayStatus): bool
    {
        return in_array($displayStatus, ['active', 'expiring_soon'], true);
    }
}
