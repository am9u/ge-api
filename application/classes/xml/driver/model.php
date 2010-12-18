<?php defined('SYSPATH') or die('No direct script access.');

abstract class XML_Driver_Model extends XML
{
	public $root_node = 'models';

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

    protected function _add_model($type, $model)
    {
        $node = $this->add_node($type, NULL, array('id' => $model->id));
        foreach($this->_schema as $child_key => $child_val)
        {
            if(count($child_val) === 0)
            {
                $node->add_node($child_key, $model->__get($child_key));
            }
        }
        return $this;
    }

    public function add_model($model) {}

    public function add_models_as_nodes($data)
    {
        // single view
        if($data->count_all() === 1)
        {
            Kohana::$log->add('debug', get_class($this).'::add_models_as_nodes() -- single instance view for '.get_class($data));
            $this->add_model($data->find());
        }
        // collection view
        else
        {
            Kohana::$log->add('debug', get_class($this).'::add_models_as_nodes() -- multiple instance view for '.get_class($data));
            foreach($data->find_all() as $model)
            {
                $this->add_model($model);
            }
        }

        return $this;
    }

	public function normalize_datetime($value)
	{
		if ( ! is_numeric($value))
		{
			$value = strtotime($value);
		}

		// Convert timestamps to RFC 3339 formatted datetime
		return date(DATE_RFC3339, $value);
	}
	
	public function normalize_date($value)
	{
		if ( ! is_numeric($value))
		{
			$value = strtotime($value);
		}

		// Convert timestamps to RFC 3339 formatted dates
		return date("Y-m-d", $value);
	}

}
