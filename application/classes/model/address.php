<?php defined('SYSPATH') or die('No direct script access.');

class Model_Address extends ORM 
{
    protected $_db = 'event_warehouse';

    // relationships
    protected $_has_many = array(
        'venues' => array('through' => 'venue_address', 'foreign_key' => 'address_id', 'far_key' => 'venue_id')
    );

    // rules
    protected $_rules = array(
        'line_1' => array('not_empty' => array()),
        'city' => array('not_empty' => array()),
        'state_province' => array('not_empty' => array()),
        'zip' => array(
            'not_empty' => array(),
            'min_length' => array(5)
        ),
    );

}

