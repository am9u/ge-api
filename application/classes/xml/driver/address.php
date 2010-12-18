
<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Address extends XML_Driver_Model
{
	public $root_node = 'addresses';

	protected static function initialize(XML_Meta $meta)
	{
		$meta
            ->content_type("application/xml")
			->nodes(
							array(
								"addresses"		    => array(),
								"address"	    	=> array('attributes' => array('id' => NULL)),
								"line_1"			=> array(),
								"line_2"			=> array(),
								"city"   			=> array(),
								"state_province"	=> array(),
								"zip"	            => array(),
								)
						);
	}

    public function add_model($model, $node_only = FALSE)
    {
        $address = $this->add_node('address', NULL, array('id' => $model->id));
        $address->add_node('line_1', $model->line_1);
        $address->add_node('line_2', $model->line_2);
        $address->add_node('city', $model->city);
        $address->add_node('state_province', $model->state_province);
        $address->add_node('zip', $model->zip);
        
        return ($node_only) ? $address : $this;
    }
}
