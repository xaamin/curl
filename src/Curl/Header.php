<?php 
namespace Xaamin\Curl\Curl;

/**
 * Handles CURL headers
 **/
class Header
{
	protected $headers = [];

	/**
	 * Constructor
	 * 
	 * @param array 	Headers
	 */
	public function __construct(array $headers = [])
	{
		if (count($headers)) {
            $this->headers = $headers;
        }
	}
    
    /**
     * Parse headers and set these properly
     * 
     * @param string|array     	$header
     * @param string    		$value
     */
    public function set($header, $value = null)
    {   
        if (is_array($header)) {
            foreach ($header as $index => $value) {
                $this->headers[$index] = $value;
            }
        } else {
            $this->headers[$header] = $value;
        }
    }

    /**
     * Fetch headers for given keys if provided,
     * otherwise retrieve all headers from response
     * 
     * @param  array    $index 	Headers or key to fetch
     * @return array
     */
    public function get($index = null, $default = null)
    {
    	if (!$index) {
    		 return $this->headers;
    	} else if (is_array($index) and count($index)) {
            $headers = [];

            foreach ($index as $header) {
                $headers[$header] = $this->fetch($header);
            }

            return $headers;
        }

        return $this->fetch($index, $default);
    }

    /**
     * Retrieve a header
     * 
     * @param   string   $index      Header index
     * @param   string   $default    Default value returned if header not exists
     * @return  string
     */
    protected function fetch($index, $default = null)
    {
    	return isset($this->headers[$index]) ? $this->headers[$index] : $default;
        
    }

    /**
     * Reset headers array
     * 
     * @return \Xaamin\Curl\Curl\Header
     */
    public function clear()
    {
        $this->headers = [];

        return $this;
    }
}