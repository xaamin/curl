<?php namespace Xaamin\Curl;

use Exception;
use Illuminate\Support\Str;
use UnexpectedValueException;
use Xaamin\Curl\Curl\Response;
use Xaamin\Curl\Curl\Exception as CurlException;

/**
 * CURL wrapper
 *
 * @package Xaamin\Curl
 * @author Benjamín Martínez Mateos <xaamin@itnovado.com>
 */
class Curl {
    
    /**
     * The file to read and write cookies to for requests
     *
     * @var string
     */
    public $cookieFile;
    
    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
     */
    public $followRedirects = true;
    
    /**
     * An associative array of headers to send along with requests
     *
     * @var array
     */
    public $headers = [];
    
    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
     */
    public $options = [];

    /**
     * An associative array of cookies to send along with requests
     *
     * @var array
     */
    public $cookies = [];
        
    /**
     * The user agent to send along with requests
     *
     * @var string
     */
    public $userAgent;
    
    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
     */
    protected $request;
    
    /**
     * Stores the HTTP auth credentialss
     *
     * @var $credentials
     * @access protected
     */
    protected $credentials;

    /**
     * Manual close Curl connection
     * 
     * @var boolean
     */
    private $interactive;
        
    /**
     * Constructor
     */
    public function __construct() 
    {
        if (!extension_loaded('curl')) 
        {
            throw new Exception('PHP CURL extension is not loaded');
        }

        $this->userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';
    }

    /**
     * Reset custom options and headers 
     * 
     * @return  Xaamin\Curl\Curl
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
     * 
     * @param   string   $dir    The path
     * @return  Xaamin\Curl\Curl
     */
    public function setCookieStorage($dir)
    {
        if(!$this->cookieFile)
        {
            if(!is_readable($dir) OR !file_exists($dir))
            {
                throw new UnexpectedValueException('Path to store the cookie file must be writable.');
            }

            $this->cookieFile = rtrim($dir, DIRECTORY_SEPARATOR) . '/' . 'CookieCurl.txt';
        }

        return $this;
    }
    
    /**
     * Makes an HTTP DELETE request to the specified url 
     * with an optional array of parameters
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param   string  $url
     * @param   array   $params 
     * @return  Xaamin\Curl\Response
     */
    public function delete($url, array $params = []) 
    {
        return $this->request('DELETE', $url, $params);
    }
    
    /**
     * Makes an HTTP GET request to the specified url 
     * with an optional array of parameters
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param   string  $url
     * @param   array   $params 
     * @return  Xaamin\Curl\Response    
      */
    public function get($url, array $params = []) 
    {
        if (!empty($params)) 
        {
            $url .= stripos($url, '?') !== false ? '&' : '?';
            $url .= http_build_query((array) $params, '', '&');
        }

        return $this->request('GET', $url);
    }
    
    /**
     * Makes an HTTP HEAD request to the specified url 
     * with an optional array of parameters
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param   string  $url
     * @param   array   $params
     * @return  Xaamin\Curl\Response    
      */
    public function head($url, array $params = []) 
    {
        return $this->request('HEAD', $url, $params);
    }
    
    /**
     * Makes an HTTP POST request to the specified url 
     * with an optional array of parameters
     *
     * @param   string  $url
     * @param   array   $params 
     * @return  Xaamin\Curl\Response
     */
    public function post($url, array $params = [], $enctype = null) 
    {
        return $this->request('POST', $url, $params, $enctype);
    }
    
    /**
     * Makes an HTTP PUT request to the specified url 
     * with an optional array of parameters
     *
     * Returns a Response if the request was successful, false otherwise
     *
     * @param   string  $url
     * @param   array   $params 
     * @return  Xaamin\Curl\Response
     */
    public function put($url, $params = []) 
    {
        return $this->request('PUT', $url, $params);
    }
    
    /**
     * Makes an HTTP request of the specified $method to url 
     * with an optional array
     *
     * Returns a Response if the request was successful, 
     * false otherwise
     *
     * @param   string  $method
     * @param   string  $url
     * @param   mixed   $params
     * @return  Xaamin\Curl\Response
     */
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
            throw new CurlException(curl_error($this->request), curl_errno($this->request));
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
     * 
     * @param   array $array 
     * @return  array
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
     * 
     * @return  Xaamin\Curl\Curl
     */
    public function setInteractive()
    {
        $this->interactive = true;

        return $this;
    }

    /**
     * Open a CURL connection
     * 
     * @return  Xaamin\Curl\Curl
     */
    public function open($url = null)
    {
        if(!$this->request || !$this->interactive)
        {
            $this->request = curl_init($url);

            $this
                ->setRequestOptions()
                ->setRequestHeaders();
        }

        return $this;
    }

    /**
     * Close CURL connection
     * 
     * @return  Xaamin\Curl\Curl
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
     * 
     * @param   string $userAgent
     * @return  Xaamin\Curl\Curl
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        
        return $this;
    }

    /**
     * Set the HTTP_REFERER header
     * 
     * @param   string   $referer   The referer header to send along with requests
     * @return  Xaamin\Curl\Curl
     */
    public function setReferer($referer)
    {
        $this->option('REFERER', $referer);
        return $this;
    }

    /**
     * Set a proxy for use during request
     * 
     * @param   string  $proxy  The proxy address
     * @param   int     $port   The port
     * @param   string  $type   Proxy type
     * @return  Xaamin\Curl\Curl
     */
    public function setProxy($proxy, $port = null, $type = 'http')
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
     * @param   string  $username
     * @param   string  $password
     * @return  Xaamin\Curl\Curl
     */
    public function setAuth($username, $password = null)
    {
        if ($username) 
        {
            $this->option(['HTTPAUTH' => CURLAUTH_BASIC, 'USERPWD', (string) $username . ':' . (string) $password]); 
        }
      
        return $this;
    }

    /**
     * Set the max seconds to connect timeout 
     * and max operation timeout for current request
     * 
     * @param   int     $connect 
     * @param   int     $timeout 
     * @return  Xaamin\Curl\Curl
     */
    public function setTimeout($connect, $timeout = null)
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
     * 
     * @param   string  $key    Key Option
     * @param   string  $value  Value of key
     * @return  Xaamin\Curl\Curl
     */
    public function setOption($key, $value = null)
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
     * 
     * @param   string|array $key
     * @param   string $value
     * @return  Xaamin\Curl\Curl
     */
    public function setHeader($key, $value = null)
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
     * Return header for given key
     * 
     * @param   string  $key    Header key
     * @return  string
     */
    public function getHeader($key = null, $default = null)
    {       
        return array_get($this->headers, $key, $default);
    }

    /**
     * Return headers if keys are provided,
     * otherwise return all headers
     * 
     * @param   array $keys
     * @return  array
     */
    public function getHeaders(array $keys = [])
    {        
       if(count($keys))
        {
            $headers = [];

            foreach ($keys as $header)
            {
                $cookies[] = $this->getHeader($header);
            }

            return $headers;
        }

        return $this->headers;
    }

    /**
     * Set a cookie 
     * 
     * @param   mixed    $key
     * @param   string   $value
     * @return  Xaamin\Curl\Curl
     */
    public function setCookie($key, $value = null)
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
     * Return cookie value for given key
     * 
     * @param   string  $index Header index
     * @param   string  $default Default value returned if header not exists
     * @return  string
     */
    public function getCookie($index, $default = null)
    {
        return array_get($this->cookies, $index, $default);
    }

    /**
     * Return cookies if keys are provided,
     * otherwise return all
     * 
     * @param   array $keys
     * @return  array
     */
    public function getCookies($keys = [])
    {        
        if(count($keys))
        {
            $cookies = [];

            foreach ($keys as $header)
            {
                $cookies[] = $this->getHeader($header);
            }

            return $cookies;
        }

        return $this->cookies;
    }
    
    /**
     * Format and add custom headers to the current request
     *
     * @return  Xaamin\Curl\Curl;
     */
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
     * @param   string  $method
     * @return  Xaamin\Curl\Curl
     */
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
     * 
     * @param   string $url
     * @param   string|array $params 
     * @param   $enctype
     * @return  Xaamin\Curl\Curl
     */
    protected function setUrlWithParams($url, $params, $enctype)
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
     * @return  Xaamin\Curl\Curl
     */
    protected function setRequestOptions() 
    {
        // Set some default CURL options
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

        // Set any custom CURL options
        foreach ($this->options as $option => $value) 
        {
            $this->setOpt(constant('CURLOPT_'.str_replace('CURLOPT_', '', Str::upper($option))), $value);
        }

        // Set any custom cookie
        if(count($this->cookies))
        {
            $this->setOpt(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));
        }

        return $this;
    }

    /**
     * Set CURL option for current request
     * 
     * @param   string $option
     * @param   mixed
     * @return  void
     */
    protected function setOpt($option, $value)
    {
        curl_setopt($this->request, $option, $value);
    }
    
    /**
     * Returns an associative array of curl options currently configured.
     *
     * @return array
     */
    public function getRequestOptions() 
    {
        return Xaamin\curl_getinfo\Curl($this->request);
    }

    /**
     * Close CURL conecction
     */
    public function __destruct()
    {
        $this->close();
    }

}