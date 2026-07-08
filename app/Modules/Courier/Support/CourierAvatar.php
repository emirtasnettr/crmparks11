<?php

namespace App\Modules\Courier\Support;

use App\Modules\Courier\Models\Courier;

class CourierAvatar
{
    private const COLORS = [
        'bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500',
        'bg-rose-500', 'bg-cyan-500', 'bg-indigo-500', 'bg-orange-500',
    ];

    /**
     * @return array{avatar_initials: string, avatar_color: string}
     */
    public static function forCourier(Courier $courier): array
    {
        return [
            'avatar_initials' => mb_strtoupper(
                mb_substr($courier->first_name, 0, 1).mb_substr($courier->last_name, 0, 1)
            ),
            'avatar_color' => self::COLORS[($courier->id - 1) % count(self::COLORS)],
        ];
    }
}
