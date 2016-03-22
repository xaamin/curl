<?php 
namespace Xaamin\Curl\Curl;

use Xaamin\Curl\Curl\Header;

class Response 
{
    /**
     * The body of the response without the headers block
     *
     * @var string
     */
    private $body = '';
    
    /**
     * An associative array of headers
     *
     * @var array
     */
    private $header;

    /**
     * Redirect count from response
     * 
     * @var integer
     */
    protected $redirects = 0;

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
    function __construct($response = null) 
    {
        if ($response) {
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
        $response = explode("\r\n\r\n", $response, 2 + $this->redirects);        
        $body = array_pop($response);

        if (preg_match('#HTTP/\d\.\d#', $body)) {
            $response = explode("\r\n\r\n", $body, 2);
            $body = array_pop($response);
        }

        $flatHeaders = array_pop($response);
        
        // Set body
        $this->setBody($body, $flatHeaders);
    }

    /**
     * Parse the CURL string response and set body content
     * 
     * @param   string    $response
     * @param   string    $flatHeaders
     * @return  string
     */
    protected function setBody($response, $flatHeaders)
    {
        // Extract the version and status from the first header and set headers
        $this->setHeaders($flatHeaders);

        // Remove all headers from the response body
        $this->body = str_replace($flatHeaders, '', $response);
    }

    /**
     * Parse headers and set these properly
     * 
     * @param string    $flatHeaders
     * @return void
     */
    protected function setHeaders($flatHeaders)
    {   
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

        $this->header = new Header($headers, true);
    }

    /**
     * Return header by key
     * 
     * @return mixed
     */
    public function getHeader($index = null, $default = null)
    {
        return $this->header->get($index, $default);
    }

    /**
     * Return all headers
     * 
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->header->get();
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
     * Sets the recirect count from CURL request
     * 
     * @return void
     */
    public function setRedirectCount($total)
    {
        $this->redirects = (int)$total;
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