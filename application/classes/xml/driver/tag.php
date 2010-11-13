<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Tag extends XML_Driver_Model
{
	public $root_node = 'tags';

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
        return $this->_add_model('tag', $model);
    }
}
