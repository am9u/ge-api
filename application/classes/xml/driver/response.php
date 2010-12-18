<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Response extends XML
{
	public $root_node = 'response';

	protected static function initialize(XML_Meta $meta)
	{
		$meta	->content_type("application/xml")
				->nodes (
							array(
								"response"		    => array("filter"		=> ""),
								"status"			=> array("filter"		=> "", 'attributes' => array('type' => NULL, 'code' => NULL, 'memory_usage' => '{memory_usage}', 'execution_time' => '{execution_time}')),
								)
						);
	}

    public function add_status($status)
    {
        $node = $this->add_node('status', $status['message'], array(
            'type' => $status['type'],
            'code' => $status['code'],
        ));

        return $this;
    }

}
