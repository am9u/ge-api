<?php defined('SYSPATH') or die('No direct script access.');

class Cache_Memcache extends Kohana_Cache_Memcache 
{

    private function _sanitize_value($value, $default = NULL)
    {
         // If the value wasn't found, normalise it
        if ($value === FALSE)
        {
            $value = (NULL === $default) ? NULL : $default;
        }

        // Return the value
        return $value;

    }

	/**
	 * Retrieve a cached value entry by id.
     * with multi-get support!
	 * 
	 *     // Retrieve cache entry from memcache group
	 *     $data = Cache::instance('memcache')->get('foo');
	 * 
	 *     // Retrieve cache entry from memcache group and return 'bar' if miss
	 *     $data = Cache::instance('memcache')->get('foo', 'bar');
	 *
	 * @param   string   id of cache to entry
	 * @param   string   default value to return if cache miss
	 * @return  mixed
	 * @throws  Kohana_Cache_Exception
	 */
	public function get($id, $default = NULL)
	{
        // multiget
        if(is_array($id))
        {
            array_walk($id, array($this, '_sanitize_id'));
            $values = $this->_memcache->get($id); 

            array_walk($values, array($this, '_sanitize_value'));

            $results = array_fill_keys($keys, $default)

            return array_merge($results, $values);
        }
        // simple get
        else
        {
            // Get the value from Memcache
            $value = $this->_memcache->get($this->_sanitize_id($id));

            return $this->_sanitize_value($value, $default);
        }
	}
}

