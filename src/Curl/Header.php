<?php 
namespace Xaamin\Curl\Curl;

use RuntimeException;

class Header
{
    /**
     * Headers
     * 
     * @var array
     */
	protected $headers = [];

    /**
     * Use indexes in lower case ?
     * 
     * @var boolean
     */
    protected $toLowerCase = false;

    /**
     * Read only Headers?
     * 
     * @var boolean
     */
    protected $readOnly = false;

	/**
	 * Constructor
	 * 
	 * @param array 	Headers
	 */
	public function __construct(array $headers = [], $toLowerCase = false, $readOnly = false)
	{
        $this->toLowerCase = $toLowerCase;        

        $this->set($headers);

        $this->readOnly = $readOnly;
	}

    /**
     * Generate index in right case
     * 
     * @param string $index
     * @return string
     */
    protected function getIndexRightCase($index)
    {
        return $this->toLowerCase ? strtolower($index) : $index;
    }
    
    /**
     * Parse headers and set these properly
     * 
     * @param string|array     	$header
     * @param string    		$value
     */
    public function set($header, $value = null)
    {   
        if ($this->readOnly) {
            throw new RuntimeException("Set headers value not allowed (Read only).");
        }

        if (is_array($header)) {
            foreach ($header as $index => $value) {
                $this->headers[$this->getIndexRightCase($index)] = $value;
            }
        } else {
            $this->headers[$this->getIndexRightCase($header)] = $value;
        }
    }

    /**
     * Fetch headers for given keys if provided,
     * otherwise retrieve all headers from response
     * 
     * @param  mixed    $index 	Headers or key to fetch
     * @return mixed
     */
    public function get($index = null, $default = null)
    {
    	if (!$index) {
    		 return $this->headers;
    	} else if (is_array($index) and count($index)) {
            $headers = [];

            foreach ($index as $header) {
                $headers[$this->getIndexRightCase($header)] = $this->fetch($header);
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
     * @return  mixed
     */
    protected function fetch($index, $default = null)
    {
        $index = $this->getIndexRightCase($index);

    	return isset($this->headers[$index]) ? $this->headers[$index] : $default;
        
    }

    /**
     * Headers are read only ?
     * 
     * @return boolean
     */
    public function readonly()
    {
        return $this->readOnly;
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