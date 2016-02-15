<?php 
namespace Xaamin\Curl\Curl;

/**
 * Handles CURL options
 **/
class Option
{
	protected $options = [];

	/**
	 * Constructor
	 * 
	 * @param array 	Options
	 */
	public function __construct(array $options = [])
	{
		if (count($options)) {
			$this->options = $options;
		}
	}

    /**
     * Parse options and set these properly
     * 
     * @param string|array     	$option
     * @param string    		$value
     */
    public function set($option, $value = null)
    {   
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
     * @param  array    $index 	Options or key to fetch
     * @return array
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