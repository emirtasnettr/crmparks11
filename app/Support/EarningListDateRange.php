<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class EarningListDateRange
{
    /**
     * Varsayılan: bugün dahil son 7 gün.
     *
     * @return array{date_from: string, date_to: string}
     */
    public static function resolve(?string $dateFrom, ?string $dateTo): array
    {
        $end = filled($dateTo)
            ? Carbon::parse($dateTo)->startOfDay()
            : now()->startOfDay();

        $start = filled($dateFrom)
            ? Carbon::parse($dateFrom)->startOfDay()
            : $end->copy()->subDays(6);

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
        ];
    }

    /**
     * @return array{date_from: string, date_to: string}
     */
    public static function fromRequest(Request $request): array
    {
        return self::resolve(
            $request->string('date_from')->toString() ?: null,
            $request->string('date_to')->toString() ?: null,
        );
    }

    /**
     * @param  Builder<\App\Models\EarningLine>  $query
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     */
    public static function apply(Builder $query, array $filters): void
    {
        $range = self::resolve(
            isset($filters['date_from']) ? (string) $filters['date_from'] : null,
            isset($filters['date_to']) ? (string) $filters['date_to'] : null,
        );

        $query->whereDate('work_date', '>=', $range['date_from'])
            ->whereDate('work_date', '<=', $range['date_to']);
    }
}
