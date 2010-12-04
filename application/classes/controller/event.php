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
        $events = ORM::factory($this->_model_type)->order_by('datetime', 'desc')->limit($limit);
        $this->_payload = $events->find_all();

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );
    }
}
