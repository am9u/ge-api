<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Page {

    //protected static $cache = Cache::instance('memcache');

    /**
     * Load a page from cache.
     * be nested.
     *
     * @param   string   page name
     * @return  array    cached page
     */
    public static function load($name)
    {
        $cache = Cache::instance('memcache');

        // Set the cache key name
        $cache_key = 'Page::cache('.$name.')';

        // Set the lifetime a year in the future
        $lifetime = 365 * 24 * 60 * 60;

        return $cache->get($cache_key, NULL);

        // if ($page = Kohana::cache($cache_key, NULL, $lifetime))
        // {
        //     if (time() > $page['expiry'])
        //     {
        //         // Trigger core cache cleanup code
        //         Kohana::cache($cache_key, NULL, -3600);
        //     }
        //     else
        //     {   
        //         // Return the cached page
        //         return $page;
        //     }
        // }

        // return NULL;
    }

    /**
     * Saves a page in the cache.
     *
     * @param   string   page name
     * @param   mixed    data to cache
     * @param   integer  number of seconds the cache is valid for
     * @return  void
     */
    public static function save($name, $data, $lifetime = 60)
    {
        $cache = Cache::instance('memcache');

        // Set the cache key name
        $cache_key = 'Page::cache('.$name.')';

        // Cache the page
        $cache->set($cache_key, $data, $lifetime);
    }

    /**
     * Delete a cached page.
     *
     * @param   string   page name
     * @return  void
     */
    public static function delete($name)
    {
        $cache = Cache::instance('memcache');

        // Set the cache key name
        $cache_key = 'Page::cache('.$name.')';

        // Invalid the cache
        return $cache->delete($cache_key);
    }

} // End Page

