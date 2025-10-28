<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FirebaseRealtimeService;
use App\Services\TaskTrackingService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Registrar Firebase Realtime Service como singleton
        $this->app->singleton(FirebaseRealtimeService::class, function ($app) {
            return new FirebaseRealtimeService();
        });

        // Registrar Task Tracking Service
        $this->app->singleton(TaskTrackingService::class, function ($app) {
            return new TaskTrackingService($app->make(FirebaseRealtimeService::class));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
