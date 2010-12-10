<?php defined('SYSPATH') or die('No direct script access.');

//-- Environment setup --------------------------------------------------------

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('America/New_York');

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'en_US.utf-8');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
ini_set('unserialize_callback_func', 'spl_autoload_call');

//-- Configuration and initialization -----------------------------------------

/**
 * Set Kohana::$environment if $_ENV['KOHANA_ENV'] has been supplied.
 * 
 */
if (isset($_ENV['KOHANA_ENV']))
{
	Kohana::$environment = $_ENV['KOHANA_ENV'];
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
Kohana::init(array(
	'base_url'   => '/',
    'index_file' => FALSE
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Kohana_Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	// 'auth'       => MODPATH.'auth',       // Basic authentication
	'cache'      => MODPATH.'cache',      // Caching with multiple backends
	// 'codebench'  => MODPATH.'codebench',  // Benchmarking tool
	'database'   => MODPATH.'database',   // Database access
	'image'      => MODPATH.'image',      // Image manipulation
	'orm'        => MODPATH.'orm',        // Object Relationship Mapping
	// 'oauth'      => MODPATH.'oauth',      // OAuth authentication
	// 'pagination' => MODPATH.'pagination', // Paging of results
	'unittest'   => MODPATH.'unittest',   // Unit testing
	// 'userguide'  => MODPATH.'userguide',  // User guide and API documentation
	'rest'          => MODPATH.'rest',       // REST client
	'xml'           => MODPATH.'xml',       // XML utility library 
	'rest_api'      => MODPATH.'rest_api',  // REST-ful API 
	'kohana-aws'    => MODPATH.'kohana-aws', // Kohana wrapper for php-aws library
	'mongodb-php-odm' => MODPATH.'mongodb-php-odm', // Kohana wrapper for MongoDB 
	));

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'welcome',
		'action'     => 'index',
	));

if ( ! defined('SUPPRESS_REQUEST'))
{
	/**
	 * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
	 * If no source is specified, the URI will be automatically detected.
	 */
    // Get the instance of the request
    $request = Request::instance();

    // If page cache is loaded read the request variables from the cache
    //if ($page = Page::load($_SERVER['REQUEST_URI']))
    //{
    //    if (Expires::get())
    //    {
    //        $request->status    = 304;
    //        $request->response  = ''; 
    //    }
    //    else
    //    {
    //        $request->status    = $page['status'];
    //        $request->response  = $page['response'];
    //    }

    //    $request->headers   = $page['headers'];
    //}
    //else
    //{
        // Attempt to execute the response
        $request->response = (string) $request->execute()->response;
    //}

    // Send headers and replace memory_usage and execution_time variables   
    if ($request->send_headers()->response)
    {
        // Get the total memory and execution time
        $total = array(
            '{memory_usage}'   => number_format((memory_get_peak_usage() - KOHANA_START_MEMORY) / (1024*1024), 2).'MB',
            '{execution_time}' => number_format(microtime(TRUE) - KOHANA_START_TIME, 4).' seconds'
            );

        // Insert the totals into the response
        $request->response = str_replace(array_keys($total), $total, $request->response);
    }

    /**
     * Display the request response.
     */
    echo $request->response;
}

