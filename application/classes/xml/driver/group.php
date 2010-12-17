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
								"models"		    => array("filter"		=> ""),
								"model"				=> array("filter"		=> ""),
								"name"				=> array("filter"		=> ""),
								)
						);
	}

    public function add_model($model)
    {
        //return $this->_add_model('group', $model);

        $group = $this->add_node('group', NULL, array('id' => $model->id));
        $group->add_node('name', $model->name);
        $group->add_node('description', $model->description);
    }
}
