<?php 
namespace Xaamin\Curl\Curl;

use RuntimeException;

class Option
{
	protected $options = [];

    /**
     * Read only options?
     * 
     * @var boolean
     */
    protected $readOnly = false;

	/**
	 * Constructor
	 * 
	 * @param array 	Options
	 */
	public function __construct(array $options = [], $readOnly = false)
	{
        $this->set($options);
        
        $this->readOnly = $readOnly;
	}

    /**
     * Parse options and set these properly
     * 
     * @param string|array     	$option
     * @param string    		$value
     */
    public function set($option, $value = null)
    {   
        if ($this->readOnly) {
            throw new RuntimeException("Set options value not allowed (Read only).");
        }

        if (is_array($option)) {
            foreach ($option as $index => $value) {
                $this->options[$index] = $value;
            }
        } else {
            $this->options[$option] = $value;
        }
    }

    /**
     * Fetch options for given keys if provided,
     * otherwise retrieve all options from response
     * 
     * @param  mixed    $index 	Options or key to fetch
     * @return mixed
     */
    public function get($index = null, $default = null)
    {
    	if (!$index) {
    		 return $this->options;
    	} else if (is_array($index) and count($index)) {
            $options = [];

            foreach ($index as $option) {
                $options[$option] = $this->fetch($option);
            }

            return $options;
        }

        return $this->fetch($index, $default);
    }

    /**
     * Retrieve a option
     * 
     * @param   string   $index      Header index
     * @param   string   $default    Default value returned if option not exists
     * @return  string
     */
    protected function fetch($index, $default = null)
    {
    	return isset($this->options[$index]) ? $this->options[$index] : $default;        
    }

    /**
     * Options are read only ?
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
        $this->options = [];

        return $this;
    }
}