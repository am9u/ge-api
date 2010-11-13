<?php defined('SYSPATH') or die('No direct script access.');

class Model_Imageurl extends ORM 
{
    protected $_db = 'image_warehouse';
    protected $_sorting = array('profile_id' => 'ASC');

    protected $_has_one = array('profile' => array('foreign_key' => 'id'));
    // protected $_belongs_to = array('image' => array(), 'profile' => array());
}
