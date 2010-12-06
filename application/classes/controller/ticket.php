<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Ticket extends Controller
{
    public function action_create_ticket()
    {
        $ticket = new Model_Ticket();
        $tag_id = $ticket->create_ticket();
        print $tag_id;
    }
}
