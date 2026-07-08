<?php

namespace App\Modules\Business\Support;

use App\Modules\Business\Models\Business;

class BusinessLogo
{
    private const COLORS = [
        'bg-orange-500',
        'bg-red-500',
        'bg-emerald-500',
        'bg-blue-500',
        'bg-amber-700',
        'bg-pink-500',
        'bg-rose-600',
        'bg-lime-600',
        'bg-indigo-600',
        'bg-cyan-600',
    ];

    /**
     * @return array{logo: string, logo_color: string}
     */
    public static function initials(Business $business): array
    {
        $source = trim((string) ($business->brand_name ?: $business->company_name));
        $parts = preg_split('/\s+/', $source) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        if ($initials === '') {
            $initials = 'IS';
        }

        return [
            'logo' => mb_substr($initials, 0, 2),
            'logo_color' => self::COLORS[$business->id % count(self::COLORS)],
        ];
    }
}
