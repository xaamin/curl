<?php 
namespace Xaamin\Curl\Curl;

use RuntimeException;

class Cookie
{
	protected $cookies = [];

	/**
	 * Constructor
	 * 
	 * @param string   $headers
	 */
	public function __construct($headers)
	{
        $this->parse($headers);
	}

    protected function parse($headers)  
    {
        $headers = explode("\n", trim($headers));
        $starts = 'Set-Cookie:';  
              
        foreach ($headers as $line)  {  
            if (strpos($line, $starts) !== false) {
                $pairs = explode(';', trim(str_replace($starts, '', $line)));
                $this->set($pairs);
            }  
        }
    }

    /**
     * Parse cookies and set these properly
     * 
     * @param array     	$pairs
     */
    protected function set($pairs)
    {
        $cookie = [];
        
        foreach ($pairs as $pair) {  
            $value = explode('=', trim($pair), 2);
            
            if (count($value) == 2) {  
                $index = strtolower($value[0]);

                switch ($index) {  
                    case 'path':  
                    case 'domain':  
                        $cookie[$index] = urldecode(trim($value[1]));
                    break;  
                    case 'expires':  
                        $cookie[$index] = strtotime(urldecode(trim($value[1])));  
                    break;  
                    default:  
                        $cookie['name'] = trim($value[0]);  
                        $cookie['value'] = trim($value[1]);  
                    break;  
                }  
            }  
        }

        $this->cookies[] = $cookie;
    }

    /**
     * Fetch cookies for given keys if provided,
     * otherwise retrieve all cookies from response
     * 
     * @param  mixed    $index 	Options or key to fetch
     * @return mixed
     */
    public function get($index = null, $default = null)
    {
    	if (!$index) {
    		 return $this->cookies;
    	} else if (is_array($index) and count($index)) {
            $cookies = [];

            foreach ($index as $cookie) {
                $cookies[$cookie] = $this->fetch($cookie);
            }

            return $cookies;
        }

        return $this->fetch($index, $default);
    }

    /**
     * Retrieve a cookie
     * 
     * @param   string   $index      Cookie index
     * @param   string   $default    Default value returned if cookie not exists
     * @return  string
     */
    protected function fetch($index, $default = null)
    {
        foreach ($this->cookies as $cookie) {
            if (isset($cookie['name']) && $cookie['name'] == $index) {
                return isset($cookie['value']) ? $cookie['value'] : $default;
            }
        }

    	return $default;
    }
}