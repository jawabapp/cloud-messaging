<?php

namespace JawabApp\CloudMessaging;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FCMServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'jawab-fcm');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/jawab-fcm.php' => config_path('jawab-fcm.php'),
        ], 'jawab-fcm-config');

        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/jawab-fcm'),
        ], 'jawab-fcm-assets');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/jawab-fcm.php', 'jawab-fcm');
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Get the Telescope route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
    {
        return [
            'namespace' => 'JawabApp\CloudMessaging\Http\Controllers',
            'prefix' => config('jawab-fcm.path'),
            'middleware' => config('jawab-fcm.middleware'),
        ];
    }
}
