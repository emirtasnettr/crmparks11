<?php

namespace App\Modules\ActivityLog\Support;

final class ActivityChangeFormatter
{
    /**
     * @param  array<string, mixed>|null  $values
     */
    public static function toDisplayString(?array $values): ?string
    {
        if ($values === null || $values === []) {
            return null;
        }

        return collect($values)
            ->map(function (mixed $value, mixed $key): string {
                $formatted = self::stringify($value);

                if (is_string($key) || is_int($key)) {
                    return "{$key}: {$formatted}";
                }

                return $formatted;
            })
            ->implode(', ');
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }
}
