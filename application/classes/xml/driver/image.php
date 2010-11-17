<?php defined('SYSPATH') or die('No direct script access.');

class XML_Driver_Image extends XML_Driver_Model
{
	public $root_node = 'images';

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
        $image->add_node('name', $model->name);
        //$image->add_node('url', $model->url);

        $profiles = $image->add_node('profiles');

        foreach($model->profiles as $profile_name => $profile)
        {
            $profiles->add_node('profile', $profile['url'], array(
                'type' => $profile['type'], 
                'width' => $profile['width'], 
                'height' => $profile['height']
            ));
        }

        $tags = $image->add_node('tags');
        if(isset($model->tags))
        {
            foreach($model->tags as $tag)
            {
                $tags->add_node('tag', $tag);
            }
        }

        return $image;
    }
}
