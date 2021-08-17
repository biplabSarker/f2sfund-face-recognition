<?php

namespace W3Engineers\FaceRekognition;

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
        $this->app->make('W3Engineers\FaceRekognition\RegistrationVideoController');
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
