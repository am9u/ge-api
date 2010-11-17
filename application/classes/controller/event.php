<?php defined('SYSPATH') or die('No direct script access.');
  
class Controller_Event extends Controller_REST 
{
    protected $_model_type = 'event';

    protected $_valid_get_actions = array(
        'recent' => array(),
    );

    /**
     * An example of a custom RPC action. This isn't really REST because the action is other than index.
     */
    public function action_recent($limit = 3)
    {
        $events = CacheORM::factory($this->_model_type)->order_by('datetime', 'desc')->limit($limit);
        $this->_payload = $events->find_all();

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );
    }

    // MongoDB insert
    public function action_create()
    {
        // only needed if post is coming from CURL?
        //$this->_data = $this->_parse_form_data($_POST);
        $this->_data = $_POST;

        // xss security
        $this->_data = $this->sanitize_values($this->_data);

        // $venue = new Model_Venue();
        // $venue->set('name', $this->_data['venue_name']);
        // $venue->set('address_1', $this->_data['address_1']);
        // $venue->set('address_2', $this->_data['address_2']);
        // $venue->save();

        $event = new Model_Event();

        $event->name = $this->_data['name'];
        $event->description = $this->_data['description'];
        $event->date = new MongoDate(); // $this->_data['date'];
        //$event->venue = $venue;

        $event->tags = array(
            uniqid().'_'.$event->name.'_'.$event->date
         );

        $event->save();

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );

        // $this->_payload = $event;
    }

    public function action_index($id=NULL)
    {
        $event = new Model_Event($id);
        $event->load();

        $date = date('Y-M-d h:i:s', $event->date->sec); 
        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK. '.$date
        );
    }

}
