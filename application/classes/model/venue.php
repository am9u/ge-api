<?php defined('SYSPATH') or die('No direct script access.');

class Model_Venue extends ORM 
{
    protected $_db = 'event_warehouse';

    protected $_has_many = array(
        'addresses' => array('through' => 'venue_addresses', 'foreign_key' => 'venue_id', 'far_key' => 'address_id'),
        'events' => array()
    );
    
    // protected function address()
    // {
    //     return $this->addresses->where('is_primary', '=', TRUE)->find();
    // }

    private $_address = NULL;

    public function values($values)
    {
        Kohana::$log->add('Model_Venue->values()', 'running overwritten method');

        Kohana::$log->add('Model_Venue->values()', 'found adr_ in $key');
        $adr = array();
        foreach($values as $key => $val)
        {
            if(strpos($key, 'adr_') !== false)
            {
                $key = str_replace('adr_', '', $key);
                $adr[$key] = $val;
            }
        }

        if(count($adr) > 0)
        {
            if(isset($adr->id))
            {
                Kohana::$log->add('Model_Venue->values()', 'found address id');
                $address = $this->addresses->find();
                $address->values($adr);
            }
            else 
            {
                Kohana::$log->add('Model_Venue->values()', 'create address');
                $this->_address = ORM::factory('address')->values($adr)->save();
            }
        }

        return parent::values($values);
    }

    public function save()
    {
        parent::save();

        if( ! empty($this->_address)) 
        {
            $this->add('addresses', $this->_address);
        }
        // if( ! empty($this->_tag)) 
        // {
        //     $this->add('tags', $this->_tag);
        // }

        return $this;
    }
    
}
