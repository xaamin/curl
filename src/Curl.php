<?php 
namespace Xaamin\Curl;

use Exception;
use \Xaamin\Curl\Curl\Header;
use \Xaamin\Curl\Curl\Option;
use UnexpectedValueException;
use \Xaamin\Curl\Curl\Response;
use \Xaamin\Curl\Curl\Exception as CurlException;

class Curl 
{    
    /**
     * The file to read and write cookies to for requests
     *
     * @var string
     */
    protected $cookieFile;
    
    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
     */
    protected $followRedirects = true;
    
    /**
     * Curl headers repository instance
     *
     * @var \Xaamin\Curl\Curl\Header
     */
    protected $headerManager;
    
    /**
     * Curl options manager
     *
     * @var \Xaamin\Curl\Curl\Option
     */
    protected $optionManager;

    /**
     * Curl headers response
     *
     * @var \Xaamin\Curl\Curl\Header
     */
    protected $headerRequested;
    
    /**
     * Curl options request
     *
     * @var \Xaamin\Curl\Curl\Option
     */
    protected $optionRequested;

    /**
     * Curl response instance
     *
     * @var \Xaamin\Curl\Curl\Response
     */
    protected $response;
    
    /**
     * An associative array of CURLOPT info
     *
     * @var array
     */
    protected $requestInfo = [];
        
    /**
     * The user agent to send along with requests
     *
     * @var string
     */
    protected $userAgent;
    
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
     * The custom parameters to be sent with the request.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Cookies to be sent with the request
     * 
     * @var array
     */
    protected $cookies = [];

    /**
     * Cookies sent with the request
     * 
     * @var array
     */
    protected $cookiesRequested = [];
    
    /**
     * JSON string pattern
     * 
     * @var string
     */
    protected $jsonPattern = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';
    
    /**
     * XML string pattern
     * 
     * @var string
     */
    protected $xmlPattern = '~^(?:text/|application/(?:atom\+|rss\+)?)xml~i';

    /**
     * Files to send with request
     * 
     * @var array
     */
    protected $files = [];

    /**
     * Constructor
     * 
     * @param \Xaamin\Curl\Curl\Header  $header
     * @param \Xaamin\Curl\Curl\Option  $option
     */
    public function __construct(Header $header = null, Option $option = null) 
    {
        if (!extension_loaded('curl')) {
            throw new Exception('PHP CURL extension is not loaded');
        }

        $this->userAgent = 'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36';

        $this->headerManager = $header ? : new Header;
        $this->optionManager = $option ? : new Option;
    }

    /**
     * Reset custom options and headers 
     * 
     * @return  \Xaamin\Curl\Curl
     */
    protected function clear()
    {
        $this->optionManager->clear();
        $this->headerManager->clear();
        $this->cookies = [];
        $this->files = [];
    }

    /**
     * Set the storage path for cookie files
     * 
     * @param   string              $dir    The path
     * @param   string              $filename
     * @return  \Xaamin\Curl\Curl
     */
    public function setCookieStorage($dir, $filename = 'curl-shared-cookies.txt')
    {
        if (!$this->cookieFile) {
            if (!is_readable($dir) OR !file_exists($dir)) {
                throw new UnexpectedValueException('Path to store the cookie file must be writable.');
            }

            $this->cookieFile = rtrim($dir, DIRECTORY_SEPARATOR) . '/' . $filename;
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
        $this->parameters = $params;

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
    public function post($url, array $params = []) 
    {
        return $this->request('POST', $url, $params);
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
    public function request($method, $url, $params = null) 
    {
        $this
            ->open()
            ->setRequestMethod($method)
            ->setCookiesForRequest()
            ->setUrlWithParams($url, $params);
        
        $result = curl_exec($this->request);

        $headers = explode("\n", curl_getinfo($this->request, CURLINFO_HEADER_OUT));

        $this->headerRequested = new Header($this->parseRequestHeaders($headers), true);

        $this->optionRequested = new Option($this->optionManager->get(), true);

        $this->requestInfo = curl_getinfo($this->request);
        
        if (!$result) {
            throw new CurlException(curl_error($this->request), curl_errno($this->request));
        }

        $response = new Response($result, $this->requestInfo['redirect_count']);
        
        $this->cookiesRequested = $this->cookies;

        $this->close();
        
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
     * Atach cookies to request
     * 
     * @return \Xaamin\Curl\Curl
     */
    protected function setCookiesForRequest()
    {
        if (count($this->cookies)) {
            $cookie = [];

            foreach($this->cookies as $index => $value) {
                $cookie[] = "{$index}={$value}";
            }

            $cookie = implode('; ', $cookie);

            $this->setOption('COOKIE', $cookie);
        }

        return $this;
    }

    /**
     * Open a CURL connection
     * 
     * @return  \Xaamin\Curl\Curl
     */
    public function open($url = null)
    {
        if (!$this->request) {
            $this->request = curl_init($url);
        }
    
        $this
            ->setRequestOptions()
            ->setRequestHeaders();

        return $this;
    }

    /**
     * Close CURL connection
     * 
     * @return  \Xaamin\Curl\Curl
     */
    public function close()
    {
        if ($this->request) {
            curl_close($this->request);
            $this->request = null;
        }

        $this->clear();

        return $this;
    }

    /**
     * Set the User-Agent for request
     * 
     * @param   string $userAgent
     * @return  \Xaamin\Curl\Curl
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        
        return $this;
    }

    /**
     * Ignote SSL verification
     * 
     * @param  boolean $boolean
     * @return \Xaamin\Curl\Curl
     */
    public function ignoreSsl($boolean)
    {
        $this->optionManager->set('SSL_VERIFYPEER', (boolean)$boolean);

        return $this;
    }

    /**
     * Set the HTTP_REFERER header
     * 
     * @param   string   $referer   The referer header to send along with requests
     * @return  \Xaamin\Curl\Curl
     */
    public function setReferer($referer)
    {
        $this->optionManager->set('REFERER', $referer);

        return $this;
    }

    /**
     * Set a proxy for use during request
     * 
     * @param   string  $proxy  The proxy address
     * @param   int     $port   The port
     * @param   string  $type   Proxy type
     * @return  \Xaamin\Curl\Curl
     */
    public function setProxy($proxy, $port = null, $type = 'http')
    {
        $this->optionManager->set('PROXY', $proxy);

        if ($port) {
            $this->optionManager->set('PROXYPORT', $port);            
        }

        switch ($type) {
            case 'http':
                $this->optionManager->set('PROXYTYPE', CURLPROXY_HTTP);
                break;
            case 'socks4':
                $this->optionManager->set('PROXYTYPE', CURLPROXY_SOCKS4);
                break;
            case 'socks5':
                $this->optionManager->set('PROXYTYPE', CURLPROXY_SOCKS5);
                break;
        }

        return $this;
    }
    
    /**
     * Set the user and password for HTTP auth basic authentication method.
     *
     * @param   string  $username
     * @param   string  $password
     * @return  \Xaamin\Curl\Curl
     */
    public function setAuth($username, $password = null)
    {
        if ($username) {
            $this->optionManager->set(['HTTPAUTH' => CURLAUTH_BASIC, 'USERPWD', (string) $username . ':' . (string) $password]); 
        }
      
        return $this;
    }

    /**
     * Set the max seconds to connect timeout 
     * and max operation timeout for current request
     * 
     * @param   int     $connect 
     * @param   int     $timeout 
     * @return  \Xaamin\Curl\Curl
     */
    public function setTimeout($connect, $timeout = null)
    {
        if (!$timeout) {
            $timeout = $connect;
        }

        $this->optionManager->set(['CONNECTTIMEOUT' => $connect, 'TIMEOUT' => $timeout]);

        return $this;
    }

    /**
     * Set cookies to be sent with the request
     * 
     * @param string|array      $index
     * @param string            $value
     * @return void
     */
    public function setCookie($index, $value = null)
    {
        if (is_array($index)) {
            foreach ($index as $key => $cookie) {
                $this->cookies[$key] = $cookie;
            }
        } else {
            $this->cookies[$index] = $value;
        }
    }

    /**
     * Returns curl cookies requested
     * 
     * @param string|array      $index
     * @param string            $default
     * @return mixed
     */
    public function getCookie($index = null, $default = null)
    {
        if (is_array($index)) {
            $cookies = [];

            foreach ($index as $key => $cookie) {
                $cookies[$key] = isset($this->cookiesRequested[$key]) ? $this->cookiesRequested[$key] : $default;
            }

            return $key;
        }

        return isset($this->cookiesRequested[$index]) ? $this->cookiesRequested[$index] : $default;
    }    

    /**
     * Returns curl cookies requested
     * 
     * @return mixed
     */
    public function getCookies()
    {
        return $this->cookiesRequested;
    }

    /**
     * Set header to be sent with the request
     * 
     * @param string|array      $index
     * @param string            $value
     * @return void
     */
    public function setHeader($index, $value = null)
    {
        $this->headerManager->set($index, $value);
    }

    /**
     * Returns curl headers manager
     * 
     * @return \Xaamim\Curl\Curl\Header
     */
    public function getHeaderManager()
    {
        return $this->headerManager;
    }

    /**
     * Returns curl headers manager
     * 
     * @return \Xaamim\Curl\Curl\Header
     */
    public function header()
    {
        return $this->headerRequested;
    }

    /**
     * Returns curl headers requested
     * 
     * @param string|array      $index
     * @param string            $default
     * @return mixed
     */
    public function getHeader($index = null, $default = null)
    {
        return $this->headerRequested->get($index, $default);
    }

    /**
     * Returns curl headers requested
     * 
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headerRequested->get();
    }
    
    /**
     * Return curl options by index
     * 
     * @param string|array      $index
     * @param string            $value
     * @return void
     */
    public function setOption($index, $value)
    {
        $this->optionManager->set($index, $value);
    }

    /**
     * Return curl options manager
     * 
     * @return \Xaamim\Curl\Curl\Option
     */
    public function getOptionManager()
    {
        return $this->optionManager;
    }

    /**
     * Return curl options manager
     * 
     * @return \Xaamim\Curl\Curl\Option
     */
    public function option()
    {
        return $this->optionRequested;
    }

    /**
     * Return curl options requested
     * 
     * @return \Xaamim\Curl\Curl\Option
     */
    public function getOption($index = null, $default = null)
    {
        return $this->optionRequested->get($index, $default);
    }

    /**
     * Return curl options requested
     * 
     * @return \Xaamim\Curl\Curl\Option
     */
    public function getOptions()
    {
        return $this->optionRequested->get();
    }

    /**
     * Format and add custom headers to the current request
     *
     * @return  \Xaamin\Curl\Curl
     */
    protected function setRequestHeaders() 
    {
        $headers = [];

        foreach ($this->headerManager->get() as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->optionManager->set('HTTPHEADER', $headers);

        return $this;
    }
    
    /**
     * Set the associated CURL options for a request method
     *
     * @param   string  $method
     * @return  \Xaamin\Curl\Curl
     */
    protected function setRequestMethod($method) 
    {
        switch (strtoupper($method)) {
            case 'HEAD':
                $this->optionManager->set('NOBODY', true);
                break;
            case 'GET':
                $this->optionManager->set('HTTPGET', true);
                break;
            case 'POST':
                $this->optionManager->set('POST', true);
                break;
            default:
                $this->optionManager->set('CUSTOMREQUEST', $method);
        }

        return $this;
    }

    /**
     * Set properly the URL with params
     * 
     * @param   string $url
     * @param   string|array $params 
     * @return  \Xaamin\Curl\Curl
     */
    protected function setUrlWithParams($url, $params)
    {
        $url = $this->buildUrlFromBase($url);

        $this->optionManager->set('URL', $url);

        $this->setRequestContent($params);

        // Set any custom CURL options
        foreach ($this->optionManager->get() as $option => $value) {
            $this->setCurlOptions(constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }

        return $this;
    }

    /**
     * Set request body
     * 
     * @param  mixed    $params 
     * @return void
     */
    private function setRequestContent($params)
    {
        $isJson = $this->hasJsonContentType($params);
        $isXml = $this->hasXmlContentType($params);

        if ($isJson) {
            $params = $this->setContentToJson($params);
        }

        if (!empty($this->files)) {
            $files = $this->attachFiles();

            if(!empty($files) && is_array($params)) {
                $this->headerManager->set('Content-Type', 'multipart/form-data');
                $params += $files;
            }
        }

        if (is_array($params) && empty($this->files) && $this->headerManager->get('Content-Type') != 'multipart/form-data') {
            $params = http_build_query($params, '', '&');
        }

        if($isJson || $isXml || is_string($params)) {
            $this->setContentLength($params);
        }

        if (!empty($params)) {
            $this->optionManager->set('POSTFIELDS', $params);
        }
    }

    /**
     * Verifies if body content must be JSON
     * 
     * @param  mixed    $params 
     * @return bool
     */
    private function hasJsonContentType($params)
    {
        $header = $this->headerManager->get('Content-Type');

        if(preg_match($this->jsonPattern, $header)) return true;

        return false;
    }

    /**
     * Parses content to JSON string
     * 
     * @param  mixed    $params 
     * @return string
     */
    private function setContentToJson($params)
    {
        if (is_array($params)) {
            $params = json_encode($params);
        }

        return $params;
    }

    /**
     * Verifies if body is XML string
     * 
     * @param  mixed    $params 
     * @return bool
     */
    private function hasXmlContentType($params)
    {
        $header = $this->headerManager->get('Content-Type');

        if(preg_match($this->xmlPattern, $header)) return true;

        return false;
    }

    /**
     * Set Content-Length header for string body content
     * 
     * @param  mixed    $params 
     * @return void
     */
    private function setContentLength($params)
    {        
        $this->headerManager->set('Content-Length', strlen($params));
    }

    /**
     * Build files params
     * 
     * @return array
     */
    private function attachFiles()
    {
        $params = [];

        foreach ($this->files as $file) {
            $params[$file['name']] = '@'. realpath($file['location']);
        }

        return $params;
    }

    /**
     * Adds file to current request
     * 
     * @param string    $name
     * @param string    $location
     * @return void
     */
    public function addFile($name, $location)
    {
        $this->files[] = ['name' => $name, 'location' => $location];
    }

    /**
     * Get the URL with custom parameters.
     *
     * @param  string  $url
     * @return string
     */
    private function buildUrlFromBase($url)
    {
        if (!empty($this->parameters)) {
            $url .= stripos($url, '?') !== false ? '&' : '?';
            $url .= http_build_query((array) $this->parameters, '', '&');
        }

        $this->parameters = null;

        return $url;
    }
    
    /**
     * Set the CURLOPT options for the current request
     * 
     * @return  \Xaamin\Curl\Curl
     */
    protected function setRequestOptions() 
    {
        // Set some default CURL options
        $this->optionManager->set('HEADER', true);
        $this->optionManager->set('RETURNTRANSFER', true);
        $this->optionManager->set('USERAGENT', $this->userAgent);

        if ($this->cookieFile) {
            $this->optionManager->set('COOKIEFILE', $this->cookieFile);
            $this->optionManager->set('COOKIEJAR', $this->cookieFile);
        }

        if ($this->followRedirects) {
            $this->optionManager->set('FOLLOWLOCATION', true);
        }     

        $this->setCurlOptions(CURLINFO_HEADER_OUT, true);

        return $this;
    }

    /**
     * Set CURL option for current request
     * 
     * @param   string  $option
     * @param   mixed   $value
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
    public function getInfo() 
    {
        return $this->requestInfo;
    }

    /**
     * Set the custom parameters of the request.
     *
     * @param  array  $parameters
     * @return \Xaamin\Curl\Curl
     */
    public function with(array $parameters) 
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Returns current instance
     * 
     * @return \Xaamin\Curl\Curl
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Close CURL conecction
     */
    public function __destruct() 
    {
        $this->close();
    }
}