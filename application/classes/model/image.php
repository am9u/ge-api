<?php defined('SYSPATH') or die('No direct script access.');

class Model_Image extends ORM 
{
    protected $_db = 'image_warehouse';

    protected $_has_many = array(
        'profiles' => array('through' => 'imageurls', 'foreign_key' => 'image_id', 'far_key' => 'profile_id'),
        'imageurls' => array()
    );
}
