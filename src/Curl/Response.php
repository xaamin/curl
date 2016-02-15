<?php 
namespace Xaamin\Curl\Curl;

use Xaamin\Curl\Curl\Header;

/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 **/
class Response 
{
    /**
     * The body of the response without the headers block
     *
     * @var string
     */
    private $body = '';
    
    /**
     * An associative array of headers to send along with requests
     *
     * @var \Xaamin\Curl\Curl\Header
     */
    public $header;

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
    function __construct($response, Header $header) 
    {
        $this->header = $header;

        if ($response) {
            $this->setContent($response);
        }
    }

    /**
     * Sets raw response contents
     * 
     * @return \Xaamin\Curl\Curl\Response
     */
    public function setRawResponse($response)
    {
        $this->header->clear();
        
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
        while (count($matches)) {
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

        $this->header->set('Http-Version', array_pop($matches[1]));
        $this->header->set('Status-Code', array_pop($matches[3]));
        $this->header->set('Status', array_pop($matches[2]));

        // Exists more headers ?
        if ($headers) {
            // Convert headers into an associative array
            foreach ($headers as $header) {
                preg_match('#(.*?)\:\s(.*)#', $header, $matches);

                if (isset($matches[2]) && $header = $matches[2]) {
                    $this->header->set($matches[1], $header);
                }
            }
        }            
    }

    /**
     * Returns \Xaamim\Curl\Curl\Header
     */
    public function header()
    {
        return $this->header;
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