<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_User extends XML_Driver_Model
{
	public $root_node = 'users';

    protected $_schema = array(
        'username' => array()
    );

	protected static function initialize(XML_Meta $meta)
	{
		$meta	->content_type("application/xml")
				->nodes (
							array(
								"users"		    => array("filter"		=> ""),
								"user"			=> array("filter"		=> "", "attributes" => array("id" => NULL)),
								"username"   	=> array("filter"		=> ""),
								)
						);
	}

    public function add_model($model)
    {
        $attributes = array();
        $attributes['id'] = $model->id;

        if ( ! empty($model->token))
        {
            $attributes['token'] = $model->token;
        }

        $user = $this->add_node('user', NULL, $attributes);
        $user->add_node('username', $model->username);

        /*
        $roles_node = XML::factory('role')->add_models_as_nodes($model->roles->find_all());
        $user->import($roles_node);

        $groups_node = XML::factory('group')->add_models_as_nodes($model->groups);
        $user->import($groups_node);
        //*/

        $roles = $user->add_node('roles', NULL);

        foreach($model->roles->find_all() as $role)
        {
            $roles->add_node('role', $role->name);
        }

    }
}
