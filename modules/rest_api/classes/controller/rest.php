<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @TODO: use XML module to create responses instead of view files
 * @TODO: sanitize and validate all data coming in
 * @TODO: status codes/messages; also look into sending appropriate HTTP status code back with response
 * @TODO: implement API key/security
 * @TODO: multiple formats: json/xml. als plain text?
 */
abstract class Controller_REST extends Kohana_Controller_REST {
    
    protected $_model_type = NULL; // model type ie: 'event' or 'tag'
    protected $_model = NULL;

    protected $_view = NULL; // view
    protected $_data = NULL;
    protected $_payload = NULL;
    protected $_status = NULL;
    protected $_xml = NULL;
    protected $_cached_xml = NULL;

    private $cache = NULL;

    protected $_valid_get_actions = NULL;
    protected $_valid_post_actions = NULL;

	/**
	 * Checks the requested method against the available methods. If the method
	 * is supported, sets the request action from the map. If not supported,
	 * the "invalid" action will be called.
	 */
	public function before()
	{
		$this->_action_requested = $this->request->action;

        Kohana::$log->add('Controller_REST->before() action requested', $this->_action_requested);

        // only allow request to use REST verbs
		if ( ! isset($this->_action_map[Request::$method]))
		{
			$this->request->action = 'invalid';
		}
        // allow GET requests to call allowed methods
		else if(Request::$method === 'GET' AND isset($this->_valid_get_actions[$this->_action_requested]))
        {
			$this->request->action = $this->_action_requested;
        }
        // allow POST requests to call allowed methods
		else if(Request::$method === 'POST' AND isset($this->_valid_post_actions[$this->_action_requested]))
        {
			$this->request->action = $this->_action_requested;
        }
        // default to action map
        else
		{
			$this->request->action = $this->_action_map[Request::$method];
		}

        Kohana::$log->add($this->request->controller.'->'.$this->request->action, 'action found to invoke');

		return $this;
	}


    /**
     * Returns an Model by ID, if $id is specified. Otherwise, returns all Models in the table
     *
     * @param int $id Event ID
     * @public
     */
    public function action_index($id=NULL) 
    {
        // cache output for 60 seconds
        // $this->cache = 60;

        // 
        if ( ! empty($id)) 
        {
            $this->_model = ORM::factory($this->_model_type, $id);    
            if($this->_model->loaded())
            {
                $this->_payload = $this->_model;
            }
        }
        else 
        {
            $this->_model  = ORM::factory($this->_model_type);
            if($this->_model->count_all() > 1)
            {
                $this->_payload = $this->_model->find_all();
            }
            elseif($this->_model->count_all() === 1)
            {
                $this->_payload = $this->_model->find();
            }
        }

        $this->_status = array(
            'type'    => 'success',
            'code'    => '200',
            'message' => 'OK'
        );
    }

    /**
     * Creates new record
     * @method POST
     */
    public function action_create()
    {
        Kohana::$log->add('action_create()', 'called!');
         
        $this->_data = $this->_parse_form_data($_POST);

        // xss security
        $this->_data = $this->sanitize_values($this->_data);

        $this->_model = ORM::factory($this->_model_type);

        if($this->_model->values($this->_data)->check())
        {
            $this->_model->save();
        
            if($this->_model->saved())
            {
                Kohana::$log->add('action_create()', '$this->_model->saved() === TRUE');
                Kohana::$log->add('action_create()', '$this->_model->pk() ==='.$this->_model->pk());

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
                    'code'    => '500',
                    'message' => 'Server Error'
                );
            }
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

    /**
     * Updates record
     * @method PUT
     */ 
    public function action_update($id=NULL)
    {

        //print_r($_POST);

        $this->_data = $this->_parse_form_data($_POST);

        // xss security
        $this->_data = $this->sanitize_values($this->_data);

        // load model
        $this->_model = ORM::factory($this->_model_type, $this->_data['id']);

        // unset ID because that's the primary key!
        unset($this->_data['id']);

        if($this->_model->values($this->_data)->check())
        {
            $this->_model->save();

            if($this->_model->saved())
            {
                $this->_status = array(
                    'type'    => 'success',
                    'code'    => '200',
                    'message' => 'OK'
                );
            }
            else
            {
                $this->_status = array(
                    'type'    => 'error',
                    'code'    => '500',
                    'message' => 'Server Error'
                );
            }

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

    /**
     * Deletes record
     */
    public function action_delete($id=NULL)
    {
        if( ! empty($id))
        {
            $this->_model = ORM::factory($this->_model_type, $id);
            $this->_model->delete();

            $this->_status = array(
                'type'    => 'success',
                'code'    => '200',
                'message' => 'OK'
            );
        }
    }

    /**
     * Renders view for REST action
     *
     * @return void
     */
    protected function _render()
    {
        // render xml view
        if( ! empty($this->_xml))
        {
            // build response/status element
            $response = XML::factory('response');
            $response->add_status($this->_status);

            if( ! empty($this->_payload))
            {
                // single view
                if(count($this->_payload) === 1)
                {
                    Kohana::$log->add('_render()', 'single instance view');
                    $this->_xml->add_model($this->_payload);
                }
                // collection view
                else {
                    Kohana::$log->add('_render()', 'multiple instance view');
                    foreach($this->_payload as $data)
                    {
                        $this->_xml->add_model($data);
                    }
                }

           }
            // add payload to response/status element
            $response->import($this->_xml);

            $this->request->headers  = array('Content-Type:' => $response->content_type);
            $this->request->response = $response->render();

            if ( ! empty($this->cache))
            {
                $this->request->headers = array_merge($this->request->headers, Expires::set($this->cache));

                $cache = array(
                    'status'    => $this->request->status,
                    'headers'   => $this->request->headers,
                    'response'  => $this->request->response,
                    'expiry'    => time() + $this->cache
                );

                Page::save($_SERVER['REQUEST_URI'], $cache, $this->cache);
            }

        }
    }

    /**
     * AOP hook for post-action
     */
    public function after() 
    {
        // $this->request->headers = array('Content-Type'	=> 'text/xml; charset=utf-8');
        // $this->request->response = $this->_view;

        $this->request->status = $this->_status['code'];

        $this->_xml = XML::factory($this->_model_type);
        $this->_render();
    }

    protected function _parse_form_data($form_data)
    {
        if(count($form_data) > 1)
            return $form_data;

        $content = '';

        // join head and body of POST data into one string
        foreach($form_data as $head => $body)
        {
            $head = str_replace('_', ' ', $head);
            $content = $head.'='.$body;
        }

        // split the form data string by mime boundary
        $regex = '/-{2}(?:.*)\s*(?:Content-Disposition:\sform-data;)?-*?/';
        $data  = preg_split($regex, $content);

        if(count($data) < 1)
        {
            return $form_data;
        }
        else
        {

            // clean off first and last values in array because they're empty
            array_shift($data);
            array_pop($data); 

            // convert each value to key=value format
            $regex = '/name="(([\w\[\]\d]*))"([.\s]*)/';
            $data  = preg_replace($regex, "$1=", $data);

            // massage key/value pairs into associative array for consumption into model
            $sanitized_data = array();

            foreach($data as $k => $v)
            {
                $kv = preg_split('/=\s?/', $v);

                // single value
                if(strpos($kv[0], '[') === FALSE)
                {
                    $sanitized_data[trim($kv[0])] = trim($kv[1]);
                }
                // array of values
                else 
                {
                    $kn = explode('[', trim($kv[0]));
                    if( ! isset($sanitized_data[$kn[0]]))
                    {
                        $sanitized_data[$kn[0]] = array();
                    }
                    array_push($sanitized_data[$kn[0]], trim($kv[1]));
                }
            }

            return $sanitized_data;
        }
    }

    /**
     * A kludgey hack that should probably be re-written. This method should also 
     * probably be a static method on the Controller_REST class or a helper method
     */
    /*
    protected function _parse_form_data($form_data)
    {
        $newvalues = array();
        
        // $log = Kohana_Log::instance();

        foreach($form_data as $key => $value)
        {
            // split the form data string by mime boundary
            $regex = '/-{2}(?:.*)\s*(?:Content-Disposition:\sform-data;)?-*?/';
            $value = preg_split($regex, 'name='.$value);
            
            // clean off last value in array because it's empty
            array_pop($value); 

            // wow. this ternary for setting this regex is really bad! but it handles name=\"foo\" and name="foo"
            $regex = (Request::instance()->action === 'update') ? '/name=+(?:\\\?")([\w\[\]\d]*)(?:\\\")|(?:")/' : '/name=+"([\w\[\]\d]*)"/'; 
            if (Request::instance()->action == 'update')
            {
                $values = preg_replace($regex, '$1', $value);
            }
            else
            {
                $values = preg_replace($regex, '$1=', $value);
            }

            print_r($values);

            // massage key/value pairs into associative array for consumption into model
            foreach($values as $k => $v)
            {
                $kv = preg_split('/=\s?/', $v);
                if(strpos($kv[0], '[') === FALSE)
                {
                    $newvalues[trim($kv[0])] = trim($kv[1]);
                }
                else 
                {
                    $kn = explode('[', trim($kv[0]));
                    if( ! isset($newvalues[$kn[0]]))
                    {
                        $newvalues[$kn[0]] = array();
                    }
                    array_push($newvalues[$kn[0]], trim($kv[1]));
                }
            }
        }

        print_r($newvalues);

        return $newvalues;
    }
    //*/

    /**
     * Strips values of possible XSS attempts
     * @TODO: log when value is dirty?
     */
    protected function sanitize_values($values)
    {
        $purifier = new Purifier();
        $clean = array();
        foreach($values as $key => $value)
        {
            if(is_array($value))
            {
                $clean[$key] = $this->sanitize_values($value);
            }
            else
            {
                $clean[$key] = $purifier->clean($value);    
            }
        }

        return $clean;
    }
}
