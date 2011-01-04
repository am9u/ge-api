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

    public function add_model($model, $node_only = FALSE)
    {
        $attributes = array();
        $attributes['id'] = $model->id;

        if ( ! empty($model->token))
        {
            $attributes['token'] = $model->token;
        }

        $user = $this->add_node('user', NULL, $attributes);
        $user->add_node('username', $model->username);

        // roles
        $roles_node = XML::factory('role');
        foreach($model->roles->find_all() as $role)
        {
            $roles_node->add_simple_role_node($role);
        }
        $user->import($roles_node);

        // groups
        $groups_node = XML::factory('group');
        foreach($model->groups->find_all() as $group)
        {
            $group_node = $groups_node->add_simple_group_node($group, TRUE);

            //$group_admin_role_id = ORM::factor('role', array('name' => 'group_admin')->find()->id;

            $group_roles = ORM::factory('grouprole', array(
                    'user_id' => $model->id, 
                    'group_id' => $group->id,
                ))
                ->group_by('role_id')
                //->having('role_id', '=', $group_admin_role_id));
                ->find_all();

            $group_roles_node = $group_node->add_node('roles');
            foreach($group_roles as $group_role)
            {
                $group_roles_node->add_node('role', NULL, array('id' => $group_role->role_id, 'name' => ORM::factory('role', $group_role->role_id)->name));
            }

        }
        $user->import($groups_node);

        return ($node_only) ? $event : $this;

    }
}
