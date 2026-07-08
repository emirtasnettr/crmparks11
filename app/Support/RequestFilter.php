<?php

namespace App\Support;

use Illuminate\Http\Request;

final class RequestFilter
{
    public static function valueOrAll(Request $request, string $key): string
    {
        if (! $request->has($key)) {
            return 'all';
        }

        $value = $request->string($key)->toString();

        return $value !== '' ? $value : 'all';
    }
}
