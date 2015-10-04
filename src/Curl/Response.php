<?php namespace Xaamin\Curl\Curl;

use Illuminate\Support\Str;
/**
 * Parses the response from a Curl request into an object containing
 * the response body and an associative array of headers
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
**/
class Response {

    /**
     * The body of the response without the headers block
     *
     * @var string
    **/
    private $body = '';

    /**
     * An associative array containing the response's headers
     *
     * @var array
    **/
    private $headers = array();

    /**
     * Accepts the result of a curl request as a string
     *
     * <code>
     *      $response = new CurlResponse(curl_exec($curl_handle));
     *      echo $response;
     *      echo $response->header('Status');
     * </code>
     *
     * @param string $response
    **/
    function __construct($response) 
    {
        # Headers regex
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        # Extract headers from response
        preg_match_all($pattern, $response, $matches);

        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        # Inlude all received headers in the $headers_string
        while (count($matches[0])) 
        {
          $headers_string = array_pop($matches[0]) . $headers_string;
        }

        # Remove all headers from the response body
        $this->body = str_replace($headers_string, '', $response);

        # Extract the version and status from the first header
        $version_and_status = array_shift($headers);

        preg_match_all('#HTTP/(\d\.\d)\s((\d\d\d)\s((.*?)(?=HTTP)|.*))#', $version_and_status, $matches);
        $this->headers['HTTP-VERSION'] = array_pop($matches[1]);
        $this->headers['STATUS-CODE'] = array_pop($matches[3]);
        $this->headers['STATUS'] = array_pop($matches[2]);

        # Exists more headers ?
        if($headers)
        {
            # Convert headers into an associative array
            foreach ($headers as $header) 
            {
                preg_match('#(.*?)\:\s(.*)#', $header, $matches);
                $this->headers[Str::upper(array_get($matches, 1))] = array_get($matches, 2);
            }
        }            
    }

    /**
     * Retrieve a header from response
     * @param  string $index Header index
     * @param  string $default Default value returned if header not exists
     * @return mixed
     */
    public function header($index = null, $default = null)
    {
        return array_get($this->headers, Str::upper($index), $default);
    }

    /**
     * Fetch all header if index is not provided otherwise retrieve a header from response
     * @param  string $index Header index
     * @param  string $default Default value returned if header not exists
     * @return mixed
     */
    public function headers($index = null, $default = null)
    {
        if($index)
        {
            return $this->header($index, $default);
        }

        return $this->headers;
    }

    /**
     * Returns the response body
     *
     * <code>
     *      $curl = new Curl;
     *      $response = $curl->get('google.com');
     *      echo $response;  # => echo $response->body;
     * </code>
     *
     * @return string
    **/
    function __toString() 
    {
        return $this->body;
    }

}