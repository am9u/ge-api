<?php defined('SYSPATH') or die('No direct script access.');
  
class Controller_Group extends Controller_REST 
{
    protected $_model_type = 'group';

    protected $_valid_get_actions = array(
        'base' => array(),
        'get_by_name' => array(),
    );

    public function action_base()
    {
        $groups = ORM::factory($this->_model_type)->base_groups();
        $this->_payload = $groups;

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
        );

    }

    public function action_get_by_name($name)
    {
        $name = str_replace('-', ' ', $name);

        $this->_model = ORM::factory($this->_model_type)->where('name', '=', $name);

        $num_groups = $this->_model->count_all();
        Kohana::$log->add('debug', 'Controller_Group::action_get_by_name() -- $name='.$name.' num rows='.$num_groups);

        if($num_groups === 1)
        {
            Kohana::$log->add('debug', 'Controller_Group::action_get_by_name() -- found group by name. setting $this->_payload.');
            $this->_payload = $this->_model->where('name', '=', $name);
        }

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
        );
    }
}
