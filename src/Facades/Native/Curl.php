<?php 
namespace Xaamin\Curl\Facades\Native;

use Xaamin\Curl\Curl\Header;
use Xaamin\Curl\Curl\Option;
use Xaamin\Curl\Curl\Response;
use Xaamin\Curl\Curl as CurlWrapper;

class Curl extends Facade 
{	
    public static function create()
    {
        $header = new Header;
        $option = new Option;
        $response = new Response('', clone $header);

        return new CurlWrapper($header, $option, $response);
    }
}