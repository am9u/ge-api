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
								"models"		    => array("filter"		=> ""),
								"model"				=> array("filter"		=> ""),
								"name"				=> array("filter"		=> ""),
								"description"   	=> array("filter"		=> ""),
								)
						);
	}

    public function add_model($model)
    {
        // return $this->_add_model('venue', $model);

        $venue = $this->add_node('venue', NULL, array('id' => $model->id));
        $venue->add_node('name', $model->name);

        $adr = $model->addresses->find();

        $address = $venue->add_node('address');
        $address->add_node('line_1', $adr->line_1);
        $address->add_node('line_2', $adr->line_2);
        $address->add_node('city', $adr->city);
        $address->add_node('state_province', $adr->state_province);
        $address->add_node('zip', $adr->zip);

        return $venue;

    }
}
