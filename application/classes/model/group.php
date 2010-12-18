<?php defined('SYSPATH') or die('No direct script access.');

/**
 */
class Model_Group extends ORM 
{
    protected $_db = 'event_warehouse';

    // relationships 
    protected $_belongs_to = array(
        'parent' => array('model' => 'group', 'foreign_key' => 'parent_id'),
        'admin_role' => array('model' => 'role'),
    );

    protected $_has_many = array(
        'children' => array('model' => 'group', 'foreign_key' => 'parent_id'),

        'users'  => array('through' => 'groups_users'),
        'events' => array('through' => 'events_groups'),
    );

    // validation
    protected $_rules = array(
        'name' => array('not_empty' => array()),
    );

    /**
     * Helper function that returns all base groups
     */
    public function base_groups()
    {
        return $this
            ->where('parent_id', '=', NULL);
    }

    /**
     * Returns descendent groups of a parent group
     */
    private function _descendents($descendents)
    {
        Kohana::$log->add('debug', 'Model_Group::_descendents() ==> id='.$this->id);

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
     * Returns parent group and all descendents. Not structured into hierarchy, 
     * ie: parent > child > grandchild. Every group model is on the same level.
     */
    public function descendents($descendents = NULL)
    {
        $descendents = ORM::factory('group');
        return $this->_descendents($descendents);
    }

	/**
	 * Unassigns child groups of group to be deleted before delete action.
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

    public function system_name()
    {
        return strtolower(str_replace(' ', '_', $this->name));
    }

}


