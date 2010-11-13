<?php defined('SYSPATH') or die('No direct script access.');

class Model_Imageurl extends ORM 
{
    protected $_db = 'image_warehouse';

    protected $_belongs_to = array('image' => array(), 'profile' => array());
}
