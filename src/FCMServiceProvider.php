<?php

namespace JawabApp\CloudMessaging;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FCMServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerRoutes();

        $this->registerMigrations();

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'cloud-messaging');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/cloud-messaging.php' => config_path('cloud-messaging.php'),
        ], 'cloud-messaging-config');

        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/cloud-messaging'),
        ], 'cloud-messaging-assets');

        $this->registerCommands();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cloud-messaging.php', 'cloud-messaging');
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
            'prefix' => config('cloud-messaging.path'),
            'middleware' => config('cloud-messaging.middleware'),
        ];
    }

    /**
     * Register the package's migrations.
     *
     * @return void
     */
    private function registerMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PublishCommand::class
            ]);
        }
    }
}
