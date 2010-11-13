<?php defined('SYSPATH') or die('No direct script access.');

/**
 * HTML Purifier wrapper class based on this Kohana forums thread: 
 * http://forum.kohanaframework.org/discussion/5877/html-purifier-in-kohana-3-solved/p1
 */
class Purifier {	
	// HTML Purifier instance
	protected static $purifier;
	
	// start it
	public function __construct() {
		// Load HTML purifier
        $purifier_path = Kohana::find_file('vendor', 'htmlpurifier/4.2.0/library/HTMLPurifier.auto');
        //$purifier_path = '/home/jon/www/api.kohanaferrinho.com/vendor/htmlpurifier/4.2.0/library/HTMLPurifier.auto.php';
        Kohana::$log->add('Purfier __construct() --> purifier_path', $purifier_path);

		require $purifier_path; // '/home/jon/www/api.kohanaferrinho.com/vendor/htmlpurifier/4.2.0/library/HTMLPurifier.auto.php'; 
		
		$k = Kohana::config('html_purifier');
		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.Doctype', $k['HTML.Doctype']);
		
		Purifier::$purifier = new HTMLPurifier($config);
	}
	
	public function clean($dirty_html) {
		$clean_html = Purifier::$purifier->purify( $dirty_html );
		
		return $clean_html;
	}
}



