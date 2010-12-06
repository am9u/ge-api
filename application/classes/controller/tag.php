<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Tag extends Controller_REST
{
    protected $_model_type = 'tag';
    protected $_uses_ticket = 'Ticket_Tag';
}

