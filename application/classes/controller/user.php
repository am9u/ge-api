<?php defined('SYSPATH') or die('No direct script access.');

class Controller_User extends Controller_REST
{
    protected $_model_type = 'user';
    
    protected $_valid_post_actions = array(
        'identify' => array(),
        'add_to_group' => array(),
        'remove_from_group' => array(),
    );

    /**
     * Creates a basic user w/ login role
     */
    public function action_create()
    {
        Kohana::$log->add('action_create()', 'OVERLOADED CALLED from Controller_User!');

        parent::action_create();

        Kohana::$log->add('action_create()', '$this->_model->pk() == '.$this->_model->pk());
        
        // if user was successfully created, then assign login role
        if ($this->_status['code'] == '201')
        {
            $this->_model->add('roles', ORM::factory('role')->where('name', '=', 'login')->find());
        }
        else
        {
            Kohana::$log->add('error', 'Controller_User::action_create() -- error assigning login role to new user[id='.$this->_model->id.']');
        }
    }

    /**
     * Identifies user by username/password credentials or by user_auth_token
     * Returns user_auth_token as part of the response
     */
    public function action_identify()
    {
        $status = FALSE;
        $token  = FALSE;

        $this->_data = $this->_parse_form_data($_POST);

        // Instantiate a new user
        $this->_model = ORM::factory('user');

        if ( ! empty($this->_data['token'])) 
        {
            // Load the token and user
            $token = ORM::factory('user_token', array('token' => $this->_data['token']));

             if ($token->loaded() AND $token->user->loaded())
             {
                 //$token->save();
                 $this->_model = $token->user;
                 $this->_model->complete_login();
                 //$this->model->token = $token->token;
                 $status = TRUE;
             }
        }
        else
        {
            // Check Auth
            $status = $this->_model->login($this->_data);
        }
        
        if($status) 
        {
            if( ! $token)
            {
                // Create a new autologin token
                $token = ORM::factory('user_token');

                // Set token data
                $token->user_id = $this->_model->id;
                $token->expires = time() + Kohana::config('auth.lifetime'); // comment this out for single-use tokens
                $token->save();

                $this->_model->token = $token->token;
            }
            else
            {
                $this->_model->token = $token->token; 
            }

            $this->_status = array(
                'type'    => 'success',
                'code'    => '200',
            );

            $this->_payload = $this->_model;

        }
        else
        {
            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
            );
        }

    }

    /**
     * Adds user to a group
     */
    public function action_add_to_group()
    {
        $this->_data = $this->_parse_form_data($_POST);
        
        if (isset($this->_data['group_id']) AND isset($this->_data['user_id']))
        {
            Kohana::$log->add('debug', 'Controller_User::action_add_to_group -- user_id='.$this->_data['user_id'].' group_id='.$this->_data['group_id']);

            $user  = ORM::factory('user', $this->_data['user_id']);
            $group = ORM::factory('group', $this->_data['group_id']);

            // user and group are valid, so create relationship
            if ($user->loaded() AND $group->loaded())
            {
                $user->add('groups', $group);

                $this->_status = array(
                    'type'    => 'success',
                    'code'    => '200',
                );

                $this->_payload = $user;
            }
            // user or group not found
            else
            {
                $this->_status = array(
                    'type'    => 'error',
                    'code'    => '400',
                );
            }
        }
        // required id's are not found in $_POST
        else
        {
            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
            );
        }
    }

    /**
     * Removes a user from a group
     */
    public function action_remove_from_group()
    {
        $this->_data = $this->_parse_form_data($_POST);
        
        if(isset($this->_data['group_id']) AND isset($this->_data['user_id']))
        {
            $this->_model  = ORM::factory('user', $group_id)->find();
            $group         = ORM::factory('group', $group_id)->find();

            // user and group are valid, so remove relationship
            if($this->_model->loaded() AND $group->loaded())
            {
                $this->_model->remove('groups', $group);

                $this->_status = array(
                    'type'    => 'success',
                    'code'    => '200',
                );

                $this->_payload = $this->_model;
            }
            // user or group not found
            else
            {
                $this->_status = array(
                    'type'    => 'error',
                    'code'    => '400',
                );
            }
        }
        // required id's are not found in $_POST
        else
        {
            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
            );
        }
    }

    /**
     * Adds role 
     */
    public function action_add_role()
    {
        $this->_data = $this->_parse_form_data($_POST);
        
        if (isset($this->_data['user_id']) AND isset($this->_data['role_id']))
        {
            Kohana::$log->add('debug', 'Controller_User::action_add_role -- user_id='.$this->_data['user_id'].' role_id='.$this->_data['role_id']);

            $user = ORM::factory('user', $this->_data['user_id']);
            $role = ORM::factory('role', $this->_data['role_id']);

            // user and group are valid, so create relationship
            if ($user->loaded() AND $role->loaded())
            {
                $user->add('role', $role);

                $this->_status = array(
                    'type'    => 'success',
                    'code'    => '200',
                );

                $this->_payload = $user;
            }
            // user or group not found
            else
            {
                $this->_status = array(
                    'type'    => 'error',
                    'code'    => '400',
                );
            }
        }
        // required id's are not found in $_POST
        else
        {
            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
            );
        }
    }

    /**
     * Removes a role
     */
    public function action_remove_role()
    {
        $this->_data = $this->_parse_form_data($_POST);
        
        if (isset($this->_data['user_id']) AND isset($this->_data['role_id']))
        {
            Kohana::$log->add('debug', 'Controller_User::action_remove_role -- user_id='.$this->_data['user_id'].' role_id='.$this->_data['role_id']);

            $user = ORM::factory('user', $this->_data['user_id']);
            $role = ORM::factory('role', $this->_data['role_id']);

            // user and group are valid, so create relationship
            if ($user->loaded() AND $role->loaded())
            {
                $user->remove('role', $role);

                $this->_status = array(
                    'type'    => 'success',
                    'code'    => '200',
                );

                $this->_payload = $this->_model;
            }
            // user or group not found
            else
            {
                $this->_status = array(
                    'type'    => 'error',
                    'code'    => '400',
                );
            }
        }
        // required id's are not found in $_POST
        else
        {
            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
            );
        }
    }

}

