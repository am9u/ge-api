<?php defined('SYSPATH') or die('No direct script access.');

class Controller_User extends Controller_REST
{
    protected $_model_type = 'user';
    
    protected $_valid_post_actions = array(
        'identify' => array(),
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
        if ($this->_status['code'] == '200')
        {
            $this->_model->add('roles', ORM::factory('role')->where('name', '=', 'login')->find());
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
                'message' => 'OK'
            );

            $this->_payload = $this->_model;

        }
        else
        {
            $this->_status = array(
                'type'    => 'error',
                'code'    => '400',
                'message' => 'Bad Request'
            );
        }

    }


}

