<?php namespace Xaamin\Curl;

use Illuminate\Support\Str;
use Xaamin\Curl\Curl\Exception;
use Xaamin\Curl\Curl\Response;

/**
 * CURL wrapper
 *
 * @package Xaamin\Curl
 * @author Benjamín Martínez Mateos <xaamin@itnovado.com>
**/
class Curl {
    
    /**
     * The file to read and write cookies to for requests
     *
     * @var string
    **/
    public $cookieFile;
    
    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
    **/
    public $followRedirects = true;
    
    /**
     * An associative array of headers to send along with requests
     *
     * @var array
    **/
    public $headers = [];
    
    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
    **/
    public $options = [];

    /**
     * An associative array of cookies to send along with requests
     *
     * @var array
    **/
    public $cookies = [];
        
    /**
     * The user agent to send along with requests
     *
     * @var string
    **/
    public $userAgent;
    
    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
    **/
    protected $request;
    
    /**
     * Stores the HTTP auth credentialss
     *
     * @var $credentials
     * @access protected
    **/
    protected $credentials;

    /**
     * Manual close Curl connection
     * @var boolean
     */
    private $interactive;
        
    /**
     * Initializes a Curl object
     *
     * Set the $cookieFile to "curl_cookie.txt" in the current directory
     * Also set the $userAgent to $_SERVER['HTTP_USERAGENT'] if it exists or 'Curl/PHP '.PHP_VERSION.
    **/
    public function __construct() 
    {
        if (!extension_loaded('curl')) 
        {
            throw new \ErrorException('PHP CURL extension is not loaded');
        }

        $this->userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';
    }

    /**
     * Reset custom options and headers 
     * @return Curl
     */
    private function reset()
    {
        $this->options = [];
        $this->headers = [];
        $this->cookies = [];

        return $this;
    }

    /**
     * Set the storage path for cookie files
     * @param  string $dir The path
     * @return Curl
     */
    public function storage($dir = null)
    {
        if(!$this->cookieFile)
        {
            if(!is_readable($dir) OR !file_exists($dir))
            {
                throw new \ErrorException('Path to store the cookie file must be writable.');
            }

            $this->cookieFile = rtrim($dir, DIRECTORY_SEPARATOR) . '/' . 'CookieCurl.txt';
        }

        return $this;
    }
    
    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $params
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $params 
     * @return Response
    **/
    public function delete($url, $params = []) 
    {
        return $this->request('DELETE', $url, $params);
    }
    
    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $params
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $params 
     * @return Response    **/
    public function get($url, $params = []) 
    {
        if (!empty($params)) 
        {
            $url .= stripos($url, '?') !== false ? '&' : '?';
            $url .= is_string($params) ? $params : http_build_query((array) $params, '', '&');
        }

        return $this->request('GET', $url);
    }
    
    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $params
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $params
     * @return Response    **/
    public function head($url, $params = []) 
    {
        return $this->request('HEAD', $url, $params);
    }
    
    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $params
     *
     * @param string $url
     * @param array|string $params 
     * @return Response
    **/
    public function post($url, $params = [], $enctype = null) 
    {
        return $this->request('POST', $url, $params, $enctype);
    }
    
    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $params
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $params 
     * @return Response
    **/
    public function put($url, $params = []) 
    {
        return $this->request('PUT', $url, $params);
    }
    
    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $params
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array|string $params
     * @return Response
    **/
    public function request($method, $url, $params = null, $enctype = null) 
    {
        $this
            ->open()
            ->setRequestMethod($method)
            ->setUrlWithParams($url, $params, $enctype)
            ->reset();
        
        $response = curl_exec($this->request);

        $headers = preg_split('/\r\n/', curl_getinfo($this->request, CURLINFO_HEADER_OUT), null, PREG_SPLIT_NO_EMPTY);

        $this->headers = $this->parseRequestHeaders($headers);
        
        if (!$response) 
        {
            throw new Exception(curl_error($this->request), curl_errno($this->request));
        }

        $response = new Response($response);
        
        if(!$this->interactive)
        {
            $this->close();
        }
        
        return $response;
    }

    /**
     * Parse request headers into associative array
     * @param  array $array 
     * @return array
     */
    private function parseRequestHeaders(array $array)
    {
        $headers = [];

        foreach ($array as $header)
        {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);

            if(count($matches) > 2)
            {
                $headers[Str::upper(array_get($matches, 1))] = array_get($matches, 2);
            }
            else
            {
                preg_match('#(.*?)\s\/\sHTTP\/(.*)#', $header, $matches);
                if(count($matches))
                {
                    $headers['HTTP-VERSION'] = array_get($matches, 2);
                    $headers['REQUEST_METHOD'] = array_get($matches, 1);
                }
            }
        }

        return $headers;
    }

    /**
     * Keep CURL connection open
     * @return Curl
     */
    public function interactive()
    {
        $this->interactive = true;

        return $this;
    }

    /**
     * Open a CURL connection
     * @return Curl
     */
    public function open($url = null)
    {
        if(!$this->request || !$this->interactive)
        {
            $this->request = curl_init($url);

            $dir = dirname(__FILE__) . '/Cookie/';

            $this->storage($dir);

            $this
                ->setRequestOptions()
                ->setRequestHeaders();
        }

        return $this;
    }

    /**
     * Close CURL connection
     * @return 
     */
    public function close()
    {
        if($this->request)
        {
            curl_close($this->request);   
            $this->request = null;     
        }

        return $this;
    }

    /**
     * Set the User-Agent for request
     * @param  string 
     * @return Curl
     */
    public function agent($agent)
    {
        $this->userAgent = $agent;
        
        return $this;
    }

    /**
     * Set the HTTP_REFERER header
     * @param  string $referer The referer header to send along with requests
     * @return Curl
     */
    public function referer($referer)
    {
        $this->option('REFERER', $referer);
        return $this;
    }

    /**
     * Set a proxy for use during request
     * @param  string $proxy The proxy address
     * @param  int $port  The port
     * @param  string proxy type.
     * @return Curl
     */
    public function proxy($proxy, $port = null, $type = 'http')
    {
        $this->option('PROXY', $proxy);

        if($port)
        {
            $this->option('PROXYPORT', $port);            
        }

        switch ($type) 
        {
            case 'http':
                $this->option('PROXYTYPE', CURLPROXY_HTTP);
                break;
            case 'socks4':
                $this->option('PROXYTYPE', CURLPROXY_SOCKS4);
                break;
            case 'socks5':
                $this->option('PROXYTYPE', CURLPROXY_SOCKS5);
                break;
        }

        return $this;
    }
    
    /**
     * Set the user and password for HTTP auth basic authentication method.
     *
     * @param string $username
     * @param string|null $password
     * @return Curl
     */
    public function auth($username, $password = null)
    {
        if ($username) 
        {
            $this->option(['HTTPAUTH' => CURLAUTH_BASIC, 'USERPWD', (string) $username . ':' . (string) $password]); 
        }
      
        return $this;
    }

    /**
     * Set the max seconds to connect timeout and max operation timeout for current request
     * @param  int $connect 
     * @param  int $timeout 
     * @return Curl
     */
    public function timeout($connect, $timeout = null)
    {
        if(!$timeout)
        {
            $timeout = $connect;
        }

        $this->option(['CONNECTTIMEOUT' => $connect, 'TIMEOUT' => $timeout]);

        return $this;
    }

    /**
     * Set CURL option
     * @param string $key Key Option
     * @param string $value Value of key
     * @return Curl
     */
    public function option($key, $value = null)
    {
        if(is_array($key))
        {
            foreach ($key as $index => $value)
            {
                $this->options[$index] = $value;
            }
        }
        else
        {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Set a header 
     * @param  string|array $key
     * @param  string $value
     * @return Curl
     */
    public function header($key, $value = null)
    {
        if(is_array($key))
        {
            foreach ($key as $index => $value) 
            {
                $this->headers[$index] = $value;
            }
        }
        else
        {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * Return header if key is provided otherwise return all headers
     * @param  string|null $headers
     * @return mixed
     */
    public function headers($key = null, $default = null)
    {        
        if($key)
        {
            return array_get($this->headers, $key, $default);
        }

        return $this->headers;
    }

    /**
     * Set a cookie 
     * @param  string|array $key
     * @param  string $value
     * @return Curl
     */
    public function cookie($key, $value = null)
    {
        if(is_array($key))
        {
            foreach ($key as $index => $value) 
            {
                $this->cookies[$index] = $value;
            }
        }
        else
        {
            $this->cookies[$key] = $value;
        }

        return $this;
    }

    /**
     * Return cookie if key is provided otherwise return all cookies
     * @param  string|null $cookies
     * @return mixed
     */
    public function cookies($key = null, $default = null)
    {        
        if($key)
        {
            return array_get($this->cookies, $key, $default);
        }

        return $this->cookies;
    }
    
    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
    **/
    protected function setRequestHeaders() 
    {
        $headers = [];

        foreach ($this->headers as $key => $value) 
        {
            $headers[] = $key . ': ' . $value;
        }

        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
        return $this;
    }
    
    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
    **/
    protected function setRequestMethod($method) 
    {
        switch (Str::upper($method)) 
        {
            case 'HEAD':
                $this->setOpt(CURLOPT_NOBODY, true);
                break;
            case 'GET':
                $this->setOpt(CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                $this->setOpt(CURLOPT_POST, true);
                break;
            default:
                $this->setOpt(CURLOPT_CUSTOMREQUEST, $method);
        }

        return $this;
    }

    /**
     * Set properly the URL with params
     * @param string $url
     * @param string|array $params 
     */
    public function setUrlWithParams($url, $params, $enctype)
    {
        if (is_array($params) && $enctype != 'multipart/form-data')
        {
            $params = http_build_query($params, '', '&');
        }

        $this->setOpt(CURLOPT_URL, $url);

        if (!empty($params))
        {
            $this->setOpt(CURLOPT_POSTFIELDS, $params);
        }

        return $this;
    }
    
    /**
     * Set the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $params
     * @return void
     * @access protected
    **/
    protected function setRequestOptions() 
    {
        # Set some default CURL options
        $this->setOpt(CURLOPT_HEADER, true);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_USERAGENT, $this->userAgent);
        $this->setOpt(CURLINFO_HEADER_OUT, true);

        if ($this->cookieFile) 
        {
            $this->setOpt(CURLOPT_COOKIEFILE, $this->cookieFile);
            $this->setOpt(CURLOPT_COOKIEJAR, $this->cookieFile);
        }

        if ($this->followRedirects)
        {
            $this->setOpt(CURLOPT_FOLLOWLOCATION, true);
        }

        # Set any custom CURL options
        foreach ($this->options as $option => $value) 
        {
            $this->setOpt(constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }

        # Set any custom cookie
        if(count($this->cookies))
        {
            $this->setOpt(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));
        }

        return $this;
    }

    public function setOpt($option, $value)
    {
        curl_setopt($this->request, $option, $value);
    }
    
    /**
     * Returns an associative array of curl options
     * currently configured.
     *
     * @return array Associative array of curl options
     */
    public function getRequestOptions() 
    {
        return curl_getinfo($this->request);
    }

    public function __destruct()
    {
        $this->close();
    }

}