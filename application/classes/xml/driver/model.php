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

	public function add_person($type, $name, $email = NULL, $uri = NULL)
	{
		$author = $this->add_node($type);
		$author->add_node("name", $name);
		if ($email)
		{
			$author->add_node("email", $email);
		}
		if ($uri)
		{
			$author->add_node("uri", $uri);
		}
		return $this;
	}
	
	
	public function add_content(XML $xml_document)
	{
		$this->add_node("content", NULL, array("type" => $xml_document->meta()->content_type()))->import($xml_document);
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


	// public function render($formatted = FALSE)
	// {
	// 	if ( ! $this->published)
	// 	{
	// 		// Add the published node with current date
	// 		$this->add_node("published", time());
	// 	}
	// 	// Add the link to self
	// 	$this->add_node("link", NULL, array("rel" => "self", "href" => $_SERVER['REQUEST_URI']));
	// 	
	// 	return parent::render($formatted);
	// }


	// public function export($file)
	// {
	// 	if ( ! $this->published)
	// 	{
	// 		// Add the published node with current date
	// 		$this->add_node("published", time());
	// 	}
	// 	// Add the link to self
	// 	$this->add_node("link", NULL, array("rel" => "self", "href" => $_SERVER['REQUEST_URI']));
	// 	
	// 	parent::export($file);
	// }
}
