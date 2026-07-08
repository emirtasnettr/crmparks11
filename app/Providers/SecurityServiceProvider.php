<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            config([
                'session.secure' => env('SESSION_SECURE_COOKIE', true),
                'session.encrypt' => env('SESSION_ENCRYPT', true),
                'session.same_site' => env('SESSION_SAME_SITE', 'lax'),
            ]);
        }

        RateLimiter::for('landing-submit', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
