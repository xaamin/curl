<?php namespace Xaamin\Curl\Facades\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * CURL Facade for use on Laravel 5
 */

class Curl extends Facade 
{
	/**
	 * @return string
	 */
    protected static function getFacadeAccessor()
    {
        return 'Xaamin\Curl\Curl';
    }
}