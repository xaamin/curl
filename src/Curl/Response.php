<?php namespace Xaamin\Curl\Curl;

/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 **/
class Response {

    /**
     * The body of the response without the headers block
     *
     * @var string
     */
    private $body = '';

    /**
     * An associative array containing the response's headers
     *
     * @var array
     */
    private $headers = array();

    /**
     * Accepts the result of a curl request as a string
     *
     * <code>
     *      $response = new Response($curlResponse);
     *      echo $response;
     *      echo $response->header('Status');
     * </code>
     *
     * @param string $response
     */
    function __construct($response) 
    {
        $this->setResponseProperties($response);
    }

    /**
     * Set properties
     * 
     * @param   string $response Response from CURL request
     * @return  void
     */
    protected function setResponseProperties($response)
    {
        // Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        // Extract headers from response
        preg_match_all($pattern, $response, $matches);

        $flatHeaders = array_pop($matches[0]);

        // Set body
        $this->setBody($response, $matches[0], $flatHeaders);
    }

    /**
     * Parse the CURL string response and set body content
     * 
     * @param   string    $response
     * @param   array     $matches
     * @param   string    $flatHeaders
     * @return  string
     */
    protected function setBody($response, array $matches, $flatHeaders)
    {        
        // Inlude all received headers in the $flatHeaders
        while (count($matches))
        {
            $flatHeaders = array_pop($matches);
        }

        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $flatHeaders));

        // Extract the version and status from the first header and set headers
        $this->setHeaders($headers, array_shift($headers));

        // Remove all headers from the response body
        $this->body = str_replace($flatHeaders, '', $response);
    }

    /**
     * Parse headers and set these properly
     * 
     * @param array     $headers
     * @param string    $flatHeaders
     */
    protected function setHeaders($headers, $flatHeaders)
    {   
        preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $flatHeaders, $matches);
        $this->headers['HTTP-VERSION'] = array_pop($matches[1]);
        $this->headers['STATUS-CODE'] = array_pop($matches[3]);
        $this->headers['STATUS'] = array_pop($matches[2]);

        // Exists more headers ?
        if($headers)
        {
            // Convert headers into an associative array
            foreach ($headers as $header) 
            {
                preg_match('#(.*?)\:\s(.*)#', $header, $matches);

                if(isset($matches[2]) && $header = $matches[2])
                {
                    $this->headers[strtoupper($matches[1])] = $header;
                }
            }
        }            
    }

    /**
     * Retrieve a header from response
     * 
     * @param   string   $index      Header index
     * @param   string   $default    Default value returned if header not exists
     * @return  string
     */
    public function getHeader($index = null, $default = null)
    {
        $index = Str::upper($index);

        return isset($this->headers[$index]) ? $this->headers[$index] :$default;
    }

    /**
     * Fetch headers for given keys if provided,
     * otherwise retrieve all headers from response
     * 
     * @param  array    $keys Headers key to fetch
     * @return array
     */
    public function getHeaders(array $keys = [])
    {
        if(count($keys))
        {
            $headers = [];

            foreach ($keys as $header)
            {
                $headers[$header] = $this->getHeader($header);
            }

            return $headers;
        }

        return $this->headers;
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