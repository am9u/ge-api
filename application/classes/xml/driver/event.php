<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Event extends XML_Driver_Model
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

    public function add_model($model, $node_only = FALSE)
    {
        // base event structure
        $event = $this->add_node('event', NULL, array('id' => $model->id));
        $event->add_node('datetime', $model->datetime);
        $event->add_node('name', $model->name);
        $event->add_node('description', $model->description);

        // venue
        $venue = $model->venue;
        $venue_node = XML::factory('venue')->add_model($venue, TRUE);
        $event->import($venue_node);

        // tags
        $event_tags = $event->add_node('tags');
        if($model->tags->count_all() > 0)
        {
            foreach($model->tags->find_all() as $event_tag)
            {
                $event_tags->add_node('tag', $event_tag->name, array('id' => $event_tag->id));
            }
        }

        return ($node_only) ? $event : $this;
    }
}
