<?php defined('SYSPATH') or die('No direct access allowed.');

class Model_User extends Model_Auth_User 
{
    protected $_ignored_columns = array('token');

    protected $_has_many = array(
        'roles' => array('through' => 'roles_users'),
        'groups' => array('through' => 'groups_users'),
    );

	protected $_rules = array(
		'username' => array(
			'not_empty'  => NULL,
			'min_length' => array(4),
			'max_length' => array(32),
			'regex'      => array('/^[-\pL\pN_.]++$/uD'),
		),
		'password' => array(
			'not_empty'  => NULL,
			'min_length' => array(5),
			'max_length' => array(42),
		),
		'email' => array(
			'not_empty'  => NULL,
			'min_length' => array(4),
			'max_length' => array(127),
			'email'      => NULL,
		),
	);
	// This class can be replaced or extended

} // End User Model
