<?php 
namespace Xaamin\Curl\Curl;

use Xaamin\Curl\Curl\Header;
use Xaamin\Curl\Curl\Cookie;

class Response 
{
    /**
     * The body of the response without the headers block
     *
     * @var string
     */
    private $body = '';
    
    /**
     * Curl headers repository instance
     *
     * @var \Xaamin\Curl\Curl\Header
     */
    protected $headerManager;

    /**
     * Redirect count from response
     * 
     * @var integer
     */
    protected $redirects = 0;

    /**
     * Raw headers string
     * 
     * @var string
     */
    protected $rawHeaders;

    /**
     * Accepts the result of a curl request as a string
     *
     * <code>
     *      $response = new Response($curlResponse);
     *      echo $response;
     *      echo $response->getHeader('Status');
     * </code>
     *
     * @param string $response
     */
    function __construct($response = null, $redirects = 0) 
    {
        if ($response) {
            $this->setRedirectCount($redirects);
            $this->setRawResponse($response);
        }
    }

    /**
     * Sets raw response contents
     * 
     * @return \Xaamin\Curl\Curl\Response
     */
    public function setRawResponse($response)
    {   
        $this->setResponseProperties($response);

        return $this;
    }

    /**
     * Set properties
     * 
     * @param   string $response Response from CURL request
     * @return  void
     */
    protected function setResponseProperties($response)
    {
        $this->rawHeaders = $response;

        $response = explode("\r\n\r\n", $response, 2 + $this->redirects);  

        $body = array_pop($response);

        if (preg_match('#HTTP/\d\.\d#', $body)) {
            $response = explode("\r\n\r\n", $body, 2);
            $body = array_pop($response);
        }

        $flatHeaders = array_pop($response);
        
        $this->setHeaders($flatHeaders);
        $this->setCookies($flatHeaders);
        $this->setBody($body);
    }

    /**
     * Parse headers and set these properly
     * 
     * @param string    $flatHeaders
     * @return void
     */
    protected function setHeaders($flatHeaders)
    {   
        // Extract the version and status from the first header and set headers
        preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $flatHeaders, $matches);

        $headers = [];

        $headers['Http-Version'] = array_pop($matches[1]);
        $headers['Status-Code'] = array_pop($matches[3]);
        $headers['Status'] = array_pop($matches[2]);

        // Exists more headers ?
        // Convert headers into an associative array
        foreach (explode("\r\n", $flatHeaders) as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            if (isset($matches[2]) && $header = $matches[2]) {
                $headers[$matches[1]] = trim($header);
            }
        }

        $this->headerManager = new Header($headers, true);
    }

    /**
     * Parse the CURL string response and get cookies
     * 
     * @param   string    $response
     * @param   string    $flatHeaders
     * @return  string
     */
    protected function setCookies($flatHeaders)
    {
        // Remove all headers from the response body
        $this->cookieManager = new Cookie($flatHeaders);
    }

    /**
     * Set CURL body content
     * 
     * @param   string    $response
     * @return  string
     */
    protected function setBody($response)
    {
        $this->body = $response;
        $this->rawHeaders = str_replace($response, '', $this->rawHeaders);
    }


    /**
     * Return header by key
     * 
     * @return mixed
     */
    public function getHeader($index = null, $default = null)
    {
        return $this->headerManager->get($index, $default);
    }

    /**
     * Return all headers
     * 
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headerManager->get();
    }

    /**
     * Return header by key
     * 
     * @return mixed
     */
    public function getCookie($index = null, $default = null)
    {
        return $this->cookieManager->get($index, $default);
    }

    /**
     * Return all headers
     * 
     * @return mixed
     */
    public function getCookies()
    {
        return $this->cookieManager->get();
    }

    /**
     * Return response body
     * 
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns curl headers requested
     * 
     * @return \Xaamim\Curl\Curl\Header
     */
    public function getHeaderManager()
    {
        return $this->headerManager;
    }

    /**
     * Returns curl headers requested
     * 
     * @return \Xaamim\Curl\Curl\Header
     */
    public function header()
    {
        return $this->headerManager;
    }

    /**
     * Returns curl cookies from response
     * 
     * @return \Xaamim\Curl\Curl\Cookie
     */
    public function getCookieManager()
    {
        return $this->cookieManager;
    }

    /**
     * Returns curl headers requested
     * 
     * @return \Xaamim\Curl\Curl\Cookie
     */
    public function cookie()
    {
        return $this->cookieManager;
    }

    /**
     * Sets the recirect count from CURL request
     * 
     * @return void
     */
    public function setRedirectCount($total)
    {
        $this->redirects = (int)$total;
    }

    /**
     * Gets the headers as raw string 
     * 
     * @return string
     */
    public function getRawHeaders()
    {
        return $this->rawHeaders;
    }

    /**
     * Returns response body as string
     *
     * <code>
     *      $curl = new Curl;
     *      $response = $curl->get('google.com');
     *      echo $response;  // => echo $response->body;
     * </code>
     *
     * @return string
    **/
    function __toString() 
    {
        return $this->body;
    }
}