<?php

namespace App\Providers;

use App\Modules\ActivityLog\Services\ActivityLogService;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Setting\Contracts\SettingsGroupRepositoryInterface;
use App\Modules\Setting\Repositories\DatabaseSettingsGroupRepository;
use App\Modules\Setting\Services\AppBrandingService;
use App\Modules\Setting\Services\SettingsManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActivityLogService::class);
        $this->app->singleton(DashboardService::class);
        $this->app->singleton(SettingsManager::class);
        $this->app->singleton(AppBrandingService::class);
        $this->app->bind(SettingsGroupRepositoryInterface::class, DatabaseSettingsGroupRepository::class);
    }

    public function boot(): void
    {
        if (app()->environment('local') && ! is_link(public_path('storage'))) {
            Artisan::call('storage:link');
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        View::composer(['layouts.*', 'auth.*', 'modules.settings.*'], function ($view) {
            $view->with('branding', app(AppBrandingService::class)->resolve());
        });
    }
}
