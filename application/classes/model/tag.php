<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tags are a hierarchal structure
 */
class Model_Tag extends ORM 
{
    protected $_ticket_table = 'Ticket_Tag';

    // relationships 
    protected $_belongs_to = array(
        'parent' => array('model' => 'tag', 'foreign_key' => 'parent_id') 
    );

    protected $_has_many = array(
        'children' => array('model' => 'tag', 'foreign_key' => 'parent_id'),

        // experimental tag mapping
        'events' => array('through' => 'tagmaps'),
        'venues' => array('through' => 'tagmaps'),
        'images' => array('through' => 'tagmaps'),
    );

    // validation
    protected $_rules = array(
        'name' => array('not_empty' => array()),
    );

    // @TODO: implement unique tag name check
    // protected $_callback = array(
    //     'name' => array('name_unique'),
    // );

    /**
     * Helper function that returns all core parent tags
     */
    public function core_tags()
    {
        return $this
            ->where('parent_id', '=', NULL);
            // -> when('is_core', '=', TRUE) // do we need is_core column to identify core tags, ie: event, venue, club?
    }

    /**
     * Returns single core tag
     */
    public function core_tag($name)
    {
        return $this->core_tags()->where('name', '=', $name);
    }

    /**
     * Returns descendent tags of a parent tag
     */
    private function _descendents($descendents)
    {
        Kohana::$log->add('_descendents', 'id='.$this->id);

        $descendents->or_where('id', '=', $this->id);

        if($this->children->count_all() > 0)
        {
            foreach($this->children->find_all() as $child)
            {
                $descendents = $child->_descendents($descendents);
            }
        }

        return $descendents;
    }

    /** 
     * Returns parent tag and all descendents. Not structured into hierarchy, 
     * ie: parent > child > grandchild. Every tag model is on the same level.
     */
    public function descendents($descendents = NULL)
    {
        $descendents = ORM::factory('tag');
        return $this->_descendents($descendents);
    }

	/**
	 * Unassigns child tags of tag to be deleted before delete action.
	 *
	 * @chainable
	 * @param   mixed  id to delete
	 * @return  ORM
	 */
	public function delete($id = NULL)
	{
		if ($id === NULL)
		{
			// Use the the primary key value
			$id = $this->pk();
		}

		if ( ! empty($id) OR $id === '0')
		{
			foreach ($this->children->find_all() as $child)
			{
                $child->parent_id = NULL;
			}

            parent::delete($id);
		}

		return $this;
	}
}
