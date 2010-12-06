<?php defined('SYSPATH') or die('No direct script access.');

// @TODO: make ticket type dynamic. ie: tag vs group vs event sequence
class Model_Ticket extends Model
{
    protected $_db = 'ticket_store';
    
    public function create_ticket($table)
    {
        if($table === NULL)
        {
            throw new Exception("ticket $table cannot be NULL");
        }
        else
        {
            // create new ticket
            $sql = 'REPLACE INTO '.$table.' (stub) values (\'a\');'; 
            $ticket_id = $this->_db->query(Database::INSERT, $sql, FALSE);

            return $ticket_id[0]; 
        }
    }
}

