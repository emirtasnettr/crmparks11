<?php

namespace App\Modules\Agency\Support;

use App\Modules\Agency\Models\Agency;

class AgencyLogo
{
    private const COLORS = [
        'bg-blue-600',
        'bg-indigo-600',
        'bg-emerald-600',
        'bg-red-600',
        'bg-amber-600',
        'bg-cyan-600',
        'bg-orange-600',
        'bg-violet-600',
        'bg-slate-600',
        'bg-teal-600',
    ];

    /**
     * @return array{logo: string, logo_color: string}
     */
    public static function initials(Agency $agency): array
    {
        $source = trim((string) ($agency->brand_name ?: $agency->company_name));
        $parts = preg_split('/\s+/', $source) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        if ($initials === '') {
            $initials = 'AC';
        }

        return [
            'logo' => mb_substr($initials, 0, 2),
            'logo_color' => self::COLORS[$agency->id % count(self::COLORS)],
        ];
    }
}
