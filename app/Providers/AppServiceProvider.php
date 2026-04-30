<?php

namespace App\Providers;

use App\Repositories\Contracts\TenantRepository;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->bind(TenantRepository::class, EloquentTenantRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
