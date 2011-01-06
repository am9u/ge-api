<?php defined('SYSPATH') or die('No direct script access.');

class Model_EventGroupRole extends ORM 
{
    protected $_table_name = 'events_groups_roles';

    protected $_belongs_to = array(
            'role' => array('model' => 'role')
        );
}
