<?php

namespace MinesDev\RenaterMail;

use Illuminate\Support\ServiceProvider;

class RenaterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //$this->app->make('MinesDev\RenaterMail\RenaterApi');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/renatervendor.php' => config_path('renatervendor.php'),
        ]);
    }
}
