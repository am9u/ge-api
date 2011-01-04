<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Role extends XML_Driver_Model
{
	public $root_node = 'roles';

    protected $_schema = array(
        'name' => array(),
        'description' => array()
    );

	protected static function initialize(XML_Meta $meta)
	{
		$meta	->content_type("application/xml")
				->nodes (
							array(
								"models"		    => array("filter"		=> ""),
								"model"				=> array("filter"		=> ""),
								"name"				=> array("filter"		=> ""),
								)
						);
	}

    public function add_model($model, $node_only = FALSE)
    {
        $role = $this->add_node('role', NULL, array('id' => $model->id));
        $role->add_node('name', $model->name);
        $role->add_node('description', $model->description);

        return ($node_only) ? $role : $this;
    }


    public function add_simple_role_node($model, $node_only = FALSE)
    {
        $role = $this->add_node('role', NULL, array('id' => $model->id, 'name' => $model->name));
        return ($node_only) ? $role : $this;
    }
}

