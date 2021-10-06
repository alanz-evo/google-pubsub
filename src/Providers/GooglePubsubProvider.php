<?php

namespace AlanzEvo\GooglePubsub\Providers;

use Illuminate\Support\ServiceProvider;
use AlanzEvo\GooglePubsub\Commands\ListenPubsubMessage;

class GooglePubsubProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/config.php' => config_path('pubsub.php'),
            ], 'config');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            ListenPubsubMessage::class,
        ]);
    }
}
