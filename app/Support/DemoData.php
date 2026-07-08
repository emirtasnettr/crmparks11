<?php

namespace App\Support;

class DemoData
{
    public static function enabled(): bool
    {
        $configured = config('crmlog.demo_data');

        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOL);
        }

        return app()->environment(['local', 'testing']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    public static function records(array $records): array
    {
        return self::enabled() ? $records : [];
    }
}
