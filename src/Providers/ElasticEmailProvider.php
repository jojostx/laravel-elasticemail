<?php

namespace Jojostx\ElasticEmail\Providers;

use Jojostx\ElasticEmail\Classes\ElasticEmail;
use Illuminate\Support\ServiceProvider;

class ElasticEmailProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/elasticemail.php', 'elasticemail');

        $this->app->bind('elasticemail', function ($app) {
            return new ElasticEmail(config('elasticemail.api_key'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Config
        $this->publishes([
            __DIR__.'/../../config/elasticemail.php' => config_path('elasticemail.php'),
        ], 'elasticemail-config');
    }
}
