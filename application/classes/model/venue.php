<?php defined('SYSPATH') or die('No direct script access.');

class Model_Venue extends ORM 
{
    protected $_db = 'event_warehouse';

    // relationships
    protected $_has_many = array(
        /*
        'tags' => array('through' => 'tagmaps'),
        //*/
        'addresses' => array('through' => 'venue_addresses', 'foreign_key' => 'venue_id', 'far_key' => 'address_id'),
        'events' => array()
    );

    // validation
    protected $_rules = array(
        'name' => array('not_empty' => array()),
    );
    
    // @TODO: add primary address support
    // protected function address()
    // {
    //     return $this->addresses->where('is_primary', '=', TRUE)->find();
    // }

    private $_address = NULL; // related address. used by Model_Venue::values() for storing address data

    /**
     * Creates/updates values for related models, then calls parent::values()
     */
    public function values($values)
    {
        // find address values in $values array
        $adr = array();

        foreach($values as $key => $val)
        {
            if(strpos($key, 'adr_') !== false)
            {
                $key = str_replace('adr_', '', $key);
                $adr[$key] = $val;
            }
        }

        // check if address values were found
        if(count($adr) > 0)
        {
            // find address
            // @TODO: refactor to handle multiple and/or primary addresses
            if(isset($adr->id))
            {
                Kohana::$log->add('Model_Venue->values()', 'found address id');
                $this->_address = $this->addresses->find();
            }
            // create address
            else 
            {
                Kohana::$log->add('Model_Venue->values()', 'create address');
                $this->_address = ORM::factory('address');
            }

            // assign address values in related model
            $this->_address->values($adr);
        }

        return parent::values($values);
    }

    /**
     * Validates related models 
     */
    public function check()
    {

        if($this->_address !== NULL AND $this->_address->check() === FALSE)
        {
            return FALSE;
        }

        return parent::check();
    }

    /**
     * Saves and adds related models before saving Model data
     */
    public function save()
    {
        parent::save();

        // addresses
        if( ! empty($this->_address)) 
        {
            $this->_address->save();
            $this->add('addresses', $this->_address);
        }

        /*
        // if event is created (not updated), generate it's unique event tag
        if($this->tags->where('name', '=', $this->name.': '.$this->id)->count_all() < 1) 
        {
            $core_tag_name = ucfirst($this->_object_name);

            $event_parent_tag = ORM::factory('tag')
                                    ->core_tag($core_tag_name)
                                    ->find();

            // autocreate internal tag for this event. eventually we should tagname to be event.name_event.date, ie: A Fan Ti_2010.11.01... or something like that
            $event_tag = ORM::factory('tag')
                            ->values(array('name' => $this->name.': '.$this->id, 'parent_id' => $event_parent_tag->id))
                            ->save();

            $this->add('tags', $event_tag); 
        }

        // if( ! empty($this->_tag)) 
        // {
        //     $this->add('tags', $this->_tag);
        // }
        
        //*/

        return $this;
    }
    
}
