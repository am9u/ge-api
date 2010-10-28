<?php defined('SYSPATH') OR die('No direct access allowed.');

// controller for testing XML 
class Controller_XML extends Controller
{
    public function action_index()
    {
        $xml = XML::factory('event');
        $xml->add_event(2, 'wondee siam 2', 'love the food at rick\'s. mian kana is yummy');
        $this->request->headers  = array('Content-Type:' => $xml->content_type);
        $this->request->response = $xml->render();
    }
}
