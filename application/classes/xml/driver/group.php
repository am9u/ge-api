<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Group extends XML_Driver_Model
{
	public $root_node = 'groups';

    protected $_schema = array(
        'name' => array()
    );

	protected static function initialize(XML_Meta $meta)
	{
		$meta	->content_type("application/xml")
				->nodes (
							array(
								"groups"		    => array(),
								"group"				=> array('attributes' => array('id' => NULL)),
								"name"				=> array(),
								"description"		=> array(),
								)
						);
	}

    public function add_model($model, $node_only = FALSE)
    {
        $group = $this->add_node('group', NULL, array('id' => $model->id));
        $group->add_node('name', $model->name);
        $group->add_node('description', $model->description);

        $users = $group->add_node('users', NULL);

        foreach($model->users->find_all() as $user)
        {
            $users->add_node('user', NULL, array('id' => $user->id));
        }

        return ($node_only) ? $group : $this;
    }

    public function add_simple_group_node($model, $node_only = FALSE)
    {
        $group = $this->add_node('group', NULL, array('id' => $model->id));
        $group->add_node('name', $model->name);
        return ($node_only) ? $group : $this;
    }
}
