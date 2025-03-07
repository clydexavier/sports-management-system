<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ChallongeService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(ChallongeService::class, function ($app) {
            return new ChallongeService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
