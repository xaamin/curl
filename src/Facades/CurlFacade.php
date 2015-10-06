<?php namespace Xaamin\Curl\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CURL Facade for use on Laravel 5
 */

class CurlFacade extends Facade 
{
	/**
	 * @return string
	 */
    protected static function getFacadeAccessor()
    {
        return 'CURL';
    }
}