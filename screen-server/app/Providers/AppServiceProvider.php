<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton('SUCCESS_STATUS', function () {
            return 200;
        });
        $this->app->singleton('UNAUTHORIZED_STATUS', function () {
            return 401;
        });
        $this->app->singleton('VALIDATION_STATUS', function () {
            return 300;
        });
        Schema::defaultStringLength(191);
    }
}
