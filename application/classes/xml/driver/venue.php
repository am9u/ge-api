<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Venue extends XML_Driver_Model
{
	public $root_node = 'venues';

    protected $_schema = array(
        'name' => array(),
        'address' => array(
            'value' => array( 'addresses', 'address', '0' )
        )
    );

	protected static function initialize(XML_Meta $meta)
	{
		$meta	->content_type("application/xml")
				->nodes (
							array(
								"venues"		    => array("filter"		=> ""),
								"venue"				=> array("filter"		=> "", 'attributes' => array('id' => NULL)),
								"name"				=> array("filter"		=> ""),
								)
						);
	}

    public function add_model($model, $node_only = FALSE)
    {
        $venue = $this->add_node('venue', NULL, array('id' => $model->id));
        $venue->add_node('name', $model->name);

        $address = XML::factory('address')->add_model($model->addresses->find(), TRUE);
        $venue->import($address);

        return ($node_only) ? $venue : $this;
    }
}
