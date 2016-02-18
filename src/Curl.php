<?php 
namespace Xaamin\Curl;

use Exception;
use UnexpectedValueException;
use Xaamin\Curl\Curl\Header;
use Xaamin\Curl\Curl\Option;
use Xaamin\Curl\Curl\Response;
use Xaamin\Curl\Curl\Exception as CurlException;

/**
 * CURL wrapper
 */
class Curl 
{    
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
     * Curl headers repository instance
     *
     * @var \Xaamin\Curl\Curl\Header
     */
    public $header;
    
    /**
     * Curl options respository instance
     *
     * @var \Xaamin\Curl\Curl\Option
     */
    public $option;

    /**
     * Curl response instance
     *
     * @var \Xaamin\Curl\Curl\Response
     */
    public $response;

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
     * The user agent to send along with requests
     *
     * @var string
     */
    public $userAgent;
    
    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     */
    protected $request;
    
    /**
     * Stores the HTTP auth credentialss
     *
     * @var $credentials
     */
    protected $credentials;

    /**
     * Close automatically Curl connection ?
     * 
     * @var boolean
     */
    private $interactive = false;
    
    /**
     * Constructor
     */
    public function __construct(Header $header, Option $option, Response $response) 
    {
        if (!extension_loaded('curl')) {
            throw new Exception('PHP CURL extension is not loaded');
        }

        $this->userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';

        $this->header = $header;
        $this->option = $option;
        $this->response = $response;
    }

    /**
     * Reset custom options and headers 
     * 
     * @return  Xaamin\Curl\Curl
     */
    public function clear()
    {
        $this->option->clear();
        $this->header->clear();

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
        if (!$this->cookieFile) {
            if (!is_readable($dir) OR !file_exists($dir)) {
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
        if (!empty($params)) {
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
            ->setUrlWithParams($url, $params, $enctype);
        
        $response = curl_exec($this->request);

        $headers = explode("\n", curl_getinfo($this->request, CURLINFO_HEADER_OUT));

        $this->header->set($this->parseRequestHeaders($headers));
        
        if (!$response) {
            throw new CurlException(curl_error($this->request), curl_errno($this->request));
        }

        $this->options = $this->getRequestOptions();

        $response = $this->response->setRawResponse($response, clone $this->header);
        
        if (!$this->interactive) {
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

        foreach ($array as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);

            if (count($matches) > 2) {
                $headers[$matches[1]] = $matches[2];
            } else {
                preg_match('#(.*?)\s\/\sHTTP\/(.*)#', $header, $matches);

                if(count($matches) > 2) {
                    $headers['Http-Version'] = $matches[2];
                    $headers['Request_Method'] = $matches[1];
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
    public function setInteractive($boolean = true)
    {
        $this->interactive = $boolean;

        return $this;
    }

    /**
     * Open a CURL connection
     * 
     * @return  Xaamin\Curl\Curl
     */
    public function open($url = null)
    {
        if (!$this->request || !$this->interactive) {
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
        if ($this->request) {
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
        $this->option->set('REFERER', $referer);
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
        $this->option->set('PROXY', $proxy);

        if ($port) {
            $this->option->set('PROXYPORT', $port);            
        }

        switch ($type) {
            case 'http':
                $this->option->set('PROXYTYPE', CURLPROXY_HTTP);
                break;
            case 'socks4':
                $this->option->set('PROXYTYPE', CURLPROXY_SOCKS4);
                break;
            case 'socks5':
                $this->option->set('PROXYTYPE', CURLPROXY_SOCKS5);
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
        if ($username) {
            $this->option->set(['HTTPAUTH' => CURLAUTH_BASIC, 'USERPWD', (string) $username . ':' . (string) $password]); 
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
        if (!$timeout) {
            $timeout = $connect;
        }

        $this->option->set(['CONNECTTIMEOUT' => $connect, 'TIMEOUT' => $timeout]);

        return $this;
    }

    /**
     * Returns \Xaamim\Curl\Curl\Header
     */
    public function header()
    {
        return $this->header;
    }

    /**
     * Returns \Xaamim\Curl\Curl\Option
     */
    public function option()
    {
        return $this->option;
    }

    /**
     * Returns CURL options for request
     */
    public function options()
    {
        return $this->options;
    }
    
    /**
     * Format and add custom headers to the current request
     *
     * @return  Xaamin\Curl\Curl;
     */
    protected function setRequestHeaders() 
    {
        $headers = [];

        foreach ($this->header->get() as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->setCurlOptions(CURLOPT_HTTPHEADER, $headers);

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
        switch (strtoupper($method)) {
            case 'HEAD':
                $this->setCurlOptions(CURLOPT_NOBODY, true);
                break;
            case 'GET':
                $this->setCurlOptions(CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                $this->setCurlOptions(CURLOPT_POST, true);
                break;
            default:
                $this->setCurlOptions(CURLOPT_CUSTOMREQUEST, $method);
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
        if (is_array($params) && $enctype != 'multipart/form-data') {
            $params = http_build_query($params, '', '&');
        }

        $this->setCurlOptions(CURLOPT_URL, $url);

        if (!empty($params)) {
            $this->setCurlOptions(CURLOPT_POSTFIELDS, $params);
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
        $this->setCurlOptions(CURLOPT_HEADER, true);
        $this->setCurlOptions(CURLOPT_RETURNTRANSFER, true);
        $this->setCurlOptions(CURLOPT_USERAGENT, $this->userAgent);
        $this->setCurlOptions(CURLINFO_HEADER_OUT, true);

        if ($this->cookieFile) {
            $this->setCurlOptions(CURLOPT_COOKIEFILE, $this->cookieFile);
            $this->setCurlOptions(CURLOPT_COOKIEJAR, $this->cookieFile);
        }

        if ($this->followRedirects) {
            $this->setCurlOptions(CURLOPT_FOLLOWLOCATION, true);
        }

        // Set any custom CURL options
        foreach ($this->option->get() as $option => $value) {
            $this->setCurlOptions(constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
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
    protected function setCurlOptions($option, $value)
    {
        curl_setopt($this->request, $option, $value);
    }
    
    /**
     * Returns an associative array of curl options currently configured.
     *
     * @return array
     */
    protected function getRequestOptions() 
    {
        return curl_getinfo($this->request);
    }

    /**
     * Close CURL conecction
     */
    public function __destruct()
    {
        $this->close();
    }
}