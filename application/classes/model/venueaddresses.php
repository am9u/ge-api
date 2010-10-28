<?php defined('SYSPATH') or die('No direct script access.');

class Model_Venue_Addresses extends ORM 
{
    protected $_belongs_to = array('venue' => array(), 'address' => array());
}
