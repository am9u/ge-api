<?php defined('SYSPATH') or die('No direct script access.');

class TicketFactory
{
   protected $_table;

   public function __construct($table = 'Ticket_Tag')
   {
        $this->_table = $table;         
   }

   public function create_ticket()
   {
        $ticket = new Model_Ticket;
        return $ticket->create_ticket($this->_table);
   }
}

