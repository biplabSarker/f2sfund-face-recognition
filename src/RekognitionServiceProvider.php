<?php

namespace F2SFund\FaceRekognition;

use Illuminate\Support\ServiceProvider;

class RekognitionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('F2SFund\FaceRekognition\RegistrationVideoController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/routes.php';

        $this->loadViewsFrom(__DIR__.'/views', 'calculator');
    }
}
