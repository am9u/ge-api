<?php defined('SYSPATH') or die('No direct script access.');
  
class Controller_Event extends Controller_REST 
{
    protected $_model_type = 'event';

    protected $_valid_get_actions = array(
        'recent' => array(),
        'by_group' => array(),
    );

    public function action_create()
    {
        parent::action_create();

        if(isset($this->_data['group_id']))
        {
            $group = ORM::factory('group', $this->_data['group_id']);
            $this->_model->add('groups', $group);
        }
    }

    /**
     * An example of a custom RPC action. This isn't really REST because the action is other than index.
     */
    public function action_recent($limit = 3)
    {
        $events = ORM::factory($this->_model_type)->order_by('datetime', 'desc')->limit($limit);
        $this->_payload = $events->find_all();

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );
    }

    public function action_by_group($group_id)
    {
        $events = ORM::factory('group', $group_id)->events;

        $this->_payload = $events;

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );
    }
}
