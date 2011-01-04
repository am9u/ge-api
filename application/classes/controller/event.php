<?php defined('SYSPATH') or die('No direct script access.');
  
class Controller_Event extends Controller_REST 
{
    protected $_model_type = 'event';

    protected $_valid_get_actions = array(
        'recent' => array(),
        'by_group' => array(),
    );

    protected $_valid_post_actions = array(
        'add_to_group' => array(),
        'make_public' => array(),
    );

    public function action_create()
    {
        parent::action_create();

        if(isset($this->_data['group_id']))
        {
            $group_admin = ORM::factory('role')
                                ->where('name', '=', 'group_admin')
                                ->find();

            $this->_model->add_to_group($this->_data['group_id'], $group_admin->id);
        }
    }

    public function action_add_to_group()
    {
        $this->_data = $this->_parse_form_data($_POST);

        if (isset($this->_data['event_id']) AND isset($this->_data['group_id']))
        {
            $member_role = ORM::factory('role', array('name' => 'group_member'));

            $event = ORM::factory('event', $this->_data['event_id']);
            $event->add_to_group($this->_data['group_id'], $member_role);
        }
    }

    public function action_make_public()
    {
        $this->_data = $this->_parse_form_data($_POST);

        if (isset($this->_data['event_id']))
        {
            $event = ORM::factory('event', $this->_data['event_id']);
            $event->make_public();
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
        );
    }

    public function action_by_group($group_id)
    {
        $events = ORM::factory('group', $group_id)->events;

        $this->_payload = $events;

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
        );
    }
}
