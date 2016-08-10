<?php

namespace Split\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Split\Impl\Configuration;

class SplitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        /*FIXme*/
        $this->app->singleton('test',function ($app){
            return new \A_chen(Carbon::now());
        });
        $this->app->singleton('split_redis',function ($app){
            return $this->app->make('redis')->connection();
        });
        $this->app->singleton('split_config',function ($app){
           $config = new Configuration();
            /*TODO init config*/
            return $config;
        });
    }
}
