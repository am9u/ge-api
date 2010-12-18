<?php defined('SYSPATH') or die('No direct script access.');
  
class Controller_Group extends Controller_REST 
{
    protected $_model_type = 'group';

    protected $_valid_get_actions = array(
        'base' => array()
    );

    public function action_create()
    {
        parent::action_create();

        if($this->_status['code'] == '201' AND empty($this->_data['parent_id']))
        {
            $groupadmin_role = 'groupadmin_'.$this->_model->system_name();

            Kohana::$log->add('debug', 'Controller_Role::action_create() -- creating groupadmin_role='.$groupadmin_role);

            $role = ORM::factory('role');
            $role->values(array(
                'name' => $groupadmin_role,
                'description' => 'Administrative user of '.$this->_model->name.' group. Can assign events and users to this group.',
            ));
            $role->save();

            if($role->saved())
            {
                $this->_model->admin_role = $role;
                $this->_model->save();
            }
        }
    }

    public function action_base()
    {
        $groups = ORM::factory($this->_model_type)->base_groups();

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
        );

        if($groups->count_all() === 1)
        {
            $this->_payload = $groups->find();
        }
        else
        {
            $this->_payload = $groups->find_all();
        }

    }
}
