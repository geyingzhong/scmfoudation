<?php

namespace geyingzhong\ScmFoundation;

use Illuminate\Support\ServiceProvider;

class ScmFoundationServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app['foundation'] = $this->app->share(function() {
            return new BaseData();
        });
    }
}