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

    protected $_view = NULL; // view
    protected $_data = NULL;
    protected $_payload = NULL;
    protected $_status = NULL;
    protected $_xml = NULL;

    /**
     * Returns an Model by ID, if $id is specified. Otherwise, returns all Models in the table
     *
     * @param int $id Event ID
     * @public
     */
    public function action_index($id=NULL) 
    {
        if ( ! empty($id)) 
        {
            Kohana::$log->add('action_index', 'tring to load single instance based on $id='.$id);
            $model = ORM::factory($this->_model_type, $id);    
            if($model->loaded())
            {
                Kohana::$log->add('action_index', 'loaded single instance based on $id');
                $this->_payload = $model;
            }
        }
        else 
        {
            Kohana::$log->add('action_index', 'tring to load multiple instance');
            $model  = ORM::factory($this->_model_type);
            if($model->count_all() > 1)
            {
                $this->_payload = $model->find_all();
            }
            elseif($model->count_all() === 1)
            {
                $this->_payload = $model->find();
            }
        }

        //if( ! empty($this->_payload))
        //{
            $this->_status = array(
                'type'    => 'success',
                'code'    => '1',
                'message' => 'OK'
            );
        //} 
        //else 
        //{
        //    $this->_status = array(
        //        'type'    => 'error',
        //        'code'    => '500',
        //        'message' => 'Error loading '.$this->_model_type
        //    );
        //}
    }

    public function action_create()
    {
        $log = Kohana_Log::instance();
        $this->_data = $this->_parse_form_data($_POST);

        // $this->_data  = $this->_parse_form_data($_POST);
        // print_r($this->_data);

        $model = ORM::factory($this->_model_type);
        $model
            ->values($this->_data)
            ->save();

        if($model->saved())
        {
            //$this->_view = View::factory($this->_model_type.'/create');
            // $this->_view->set('status', 'success');
            // $this->_view->set('status_code', 1);
            // $this->_view->set('status_message', 'Event created');
            // $this->_view->bind($this->_model_type, $model);
            $this->_status = array(
                'type'    => 'success',
                'code'    => '1',
                'message' => 'OK'
            );
        }
        else
        {
            //$this->_view = View::factory($this->_model_type.'/error');
            // $this->_view->set('status', 'error');
            // $this->_view->set('status_code', 1);
            // $this->_view->set('status_message', 'Unable to create Event');
            $this->_status = array(
                'type'    => 'error',
                'code'    => '500',
                'message' => 'Unable to create Event'
            );
        }

        $this->_payload = $model;

        //$this->request->response = $out;
    }

    public function action_update($id=NULL)
    {
        //$values = $this->_parse_form_data($_POST);
        $this->_data = $this->_parse_form_data($_POST);

        // load model
        $model = ORM::factory($this->_model_type, $this->_data['id']);

        // unset ID because that's the primary key!
        unset($this->_data['id']);

        // update
        $model->values($this->_data)->save();

        if($model->saved())
        {
            //$this->_view = View::factory($this->_model_type/create);
            // $this->_view->set('status', 'success');
            // $this->_view->set('status_code', 1);
            // $this->_view->set('status_message', 'Event updated');
            // $this->_view->set($this->_model_type, $model);
            $this->_status = array(
                'type'    => 'success',
                'code'    => '200',
                'message' => 'OK'
            );
        }
        else
        {
            //$this->_view = View::factory($this->_model_type.'/error');
            // $this->_view->set('status', 'error');
            // $this->_view->set('status_code', 500);
            // $this->_view->set('status_message', 'Unable to update event');
            $this->_status = array(
                'type'    => 'error',
                'code'    => '500',
                'message' => 'Unable to create Event'
            );
        }

        $this->_payload = $model;
    }

    public function action_delete($id=NULL)
    {
        if( ! empty($id))
        {
            $model = ORM::factory($this->_model_type, $id);
            $model->delete();

            //$this->_view = View::factory($this->_model_type.'/success');
            //$this->_view->set('status', 'success');
            //$this->_view->set('status_code', 1);
            //$this->_view->set('status_message', 'Deleted event id '.$id);
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
        }
    }

    public function after() 
    {
        // $this->request->headers = array('Content-Type'	=> 'text/xml; charset=utf-8');
        // $this->request->response = $this->_view;

        $this->_xml = XML::factory($this->_model_type);
        $this->_render();

    }

    /**
     * A kludgey hack that should probably be re-written. This method should also probably be a static method on the Controller_REST class or a helper method
     */
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
            $regex = (Request::instance()->action === 'update') ? '/name=(?:\\\?")(\w*)(?:\\\")|(?:")/' : '/name="(\w*)"/'; 
            $values = preg_replace($regex, '$1=', $value);

            // massage key/value pairs into associative array for consumption into model
            foreach($values as $k => $v)
            {
                $kv = preg_split('/=\s/', $v);
                $newvalues[trim($kv[0])] = trim($kv[1]);
            }
        }

        print_r($newvalues);

        return $newvalues;
    }
}
