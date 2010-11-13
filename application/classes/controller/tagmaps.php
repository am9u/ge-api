<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tags are a hierarchal structure
 */
class Model_Tagmap extends ORM 
{
    protected $_db = 'tag_store';

    // relationships
    protected $_belongs_to = array(
        'tag' => array(),
        'event' => array(),
        'venue' => array(),
        'image' => array(),
        // 'textcontent' => array(), // should this be a separate model?
    );
}
