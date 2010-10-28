<?php defined('SYSPATH') or die('No direct script access.');

class Model_Address extends ORM 
{
    protected $_db = 'event_warehouse';

    protected $_has_many = array('venues' => array('through' => 'venue_address', 'foreign_key' => 'address_id', 'far_key' => 'venue_id'));

}

