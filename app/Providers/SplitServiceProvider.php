<?php

namespace Split\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Split\Impl\Configuration;
use Split\Impl\ExperimentCatalog;
use Split\Impl\InvalidPersistenceAdapterError;
use Split\Impl\User;

class SplitServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton('split_redis', function ($app) {
            return $this->app->make('redis')->connection();
        });

        $this->app->singleton('split_config', function ($app) {
            $config = new Configuration();

            /*TODO init config*/

            return $config;
        });

        $this->app->singleton('split_catalog', function ($app) {
            return new ExperimentCatalog();
        });

        $this->app->singleton('split_user', function ($app) {
            return new User();
        });

        $this->app->singleton('split_adapter', function ($app) {
            require_once app_path('Impl/Persistence/adapter.php');
            return \Split\Impl\Persistence\adapter();
        });
    }
}
