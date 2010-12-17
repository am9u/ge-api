<?php defined('SYSPATH') or die('No direct script access.');

/**
 */
class Model_Group extends ORM 
{
    protected $_db = 'event_warehouse';

    // relationships 
    protected $_belongs_to = array(
        'parent' => array('model' => 'group', 'foreign_key' => 'parent_id') 
    );

    protected $_has_many = array(
        'children' => array('model' => 'group', 'foreign_key' => 'parent_id'),

        'users'  => array('through' => 'groups_users'),
        'events' => array('through' => 'events_groups'),
    );

    // validation
    protected $_rules = array(
        'name' => array('not_empty' => array()),
    );

}


