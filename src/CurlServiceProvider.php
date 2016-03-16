<?php 
namespace Xaamin\Curl;


use Xaamin\Curl\Curl;
use Xaamin\Curl\Curl\Header;
use Xaamin\Curl\Curl\Option;
use Xaamin\Curl\Curl\Response;
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
        $this->app->singleton('Xaamin\Curl\Curl', function ($app) {
            return new Curl(new Header, new Option);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Xaamin\Curl\Curl'];
    }
}