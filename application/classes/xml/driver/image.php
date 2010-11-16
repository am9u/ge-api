<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Image extends XML_Driver_Model
{
	public $root_node = 'events';

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
								"description"   	=> array("filter"		=> ""),
								)
						);
	}

    public function add_model($model)
    {
        // return $this->_add_model('event', $model);
        $image = $this->add_node('image', NULL, array('id' => $model->id));

        // $event->add_node('datetime', $model->datetime);
        // $event->add_node('name', $model->name);
        // $event->add_node('description', $model->description);

        // $venue = $model->venue;

        // $venue_node = XML::factory('venue')->add_model($venue);

        // $event->import($venue_node);

        // $event_tags = $event->add_node('tags');

        // if($model->tags->count_all() > 0)
        // {
        //     foreach($model->tags->find_all() as $event_tag)
        //     {
        //         $event_tags->add_node('tag', $event_tag->name, array('id' => $event_tag->id));
        //     }
        // }

        return $image;
    }
}
