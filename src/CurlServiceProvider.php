<?php 
namespace Xaamin\Curl;

use Illuminate\Support\ServiceProvider;

/**
 * CURL Package Service provider
 */
class CurlServiceProvider extends ServiceProvider 
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('CURL', function ($app) {
            return $app->make('Xaamin\Curl\Curl');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['CURL'];
    }
}