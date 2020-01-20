<?php

namespace Optimus\ApiConsumer\Provider;

use Illuminate\Support\ServiceProvider as BaseProvider;
use Optimus\ApiConsumer\Router;

class LaravelServiceProvider extends BaseProvider {

    /***
     * LaravelServiceProvider constructor.
     * @param \Illuminate\Foundation\Application $app
     * needed for laravel upgrade
     */
    public function __construct(\Illuminate\Foundation\Application $app)
    {
        parent::__construct($app);

    }
    public function register()
    {
    }

    public function boot()
    {
        $this->app->singleton('apiconsumer', function(){
            $app = app();

            return new Router($app, $app['request'], $app['router']);
        });
    }

}
