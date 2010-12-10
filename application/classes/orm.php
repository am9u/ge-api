<?php defined('SYSPATH') or die('No direct script access.');
class ORM extends Kohana_ORM
{
    protected $_ticket_table = NULL;

    /**
     * Overloaded save method that gets PRIMARY_KEY for new database rows
     * from TicketFactory. Will call ORM::save if $this->_ticket_table is NULL
     */
    public function save()
    {
        // generate primary key from TicketFactory if Model is designated as using tickets
		if ( ! empty($this->_ticket_table) AND $this->empty_pk())
        {

            $data = array();
            foreach ($this->_changed as $column)
            {
                // Compile changed data
                $data[$column] = $this->_object[$column];
            }

            $ticket_factory = new TicketFactory($this->_ticket_table);
            $pk = $ticket_factory->create_ticket();

            $data[$this->_primary_key] = $pk;


            if (is_array($this->_created_column))
			{
				// Fill the created column
				$column = $this->_created_column['column'];
				$format = $this->_created_column['format'];

				$data[$column] = $this->_object[$column] = ($format === TRUE) ? time() : date($format);
			}

			$result = DB::insert($this->_table_name)
				->columns(array_keys($data))
				->values(array_values($data))
				->execute($this->_db);

			if ($result)
			{
				if ($this->empty_pk())
				{
					// Load the insert id as the primary key
					// $result is array(insert_id, total_rows)
					$this->_object[$this->_primary_key] = $result[0];
				}

				// Object is now loaded and saved
				$this->_loaded = $this->_saved = TRUE;
			}

            if ($this->_saved === TRUE)
            {
                // All changes have been saved
                $this->_changed = array();
            }

            return $this;
        }
        else
        {
            return parent::save();
        }
    }

}
