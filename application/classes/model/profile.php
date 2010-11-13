<?php defined('SYSPATH') or die('No direct script access.');

class Model_Profile extends ORM 
{
    protected $_db = 'image_warehouse';

    protected $_belongs_to = array('imageurls' => array());
    protected $_has_many = array('images' => array('through' => 'imageurls', 'foreign_key' => 'profile_id', 'far_key' => 'image_id'));
}
