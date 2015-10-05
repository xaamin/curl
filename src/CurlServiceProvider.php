<?php 
namespace Xaamin\Curl;

use Illuminate\Foundation\AliasLoader as Loader;
use Illuminate\Support\ServiceProvider;

/**
 * CURL Service provider
 *
 * @package Xaamin\Curl
 * @author Benjamín Martínez Mateos <bmxamin@gmail.com>
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
        $loader  = Loader::getInstance();
        $loader->alias('CURL', 'Xaamin\Curl\Facades\CurlFacade');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('CURL', function($app)
        {
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