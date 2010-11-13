<?php defined('SYSPATH') OR die('No direct access allowed.');

/* Based on Kohana v.2.3.4 ORM_Core
 * @package    ORM
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */

class CORM_Core {

	// Cache Settings
	protected $cachable = TRUE; // this doesn't seem useful anymore - relations are always cached
	protected $expire = 0;
	// list of keys under which to cache the object
	// the first key will actually store the object, any other keys will
	// get a link to the first object (so make sure the first key is the one that's used often)
	// use this to support loading objects on keys other than primary (eg email)
	// if no key given, the primary_key will be used
	protected $cache_keys = array();

	// Current relationships
	protected $has_one                 = array();
	protected $belongs_to              = array();
	protected $has_many                = array();
	protected $has_and_belongs_to_many = array();

	// Custom sets
	protected $sets                    = array();

	// Current object
	protected $object  = array();
	protected $changed = array();
	protected $related = array();
	protected $loaded  = FALSE;
	protected $saved   = FALSE;

	// Relations (stores ID sets)
	protected $relations = array();

	// Model table information
	protected $object_name;
	protected $object_plural;
	protected $table_name;
	protected $table_columns;
	protected $ignored_columns;

	// Table primary key and value
	protected $primary_key = 'id';
	protected $primary_val = 'name';

	// Array of foreign key name overloads
	protected $foreign_key = array();

	// Model configuration
	protected $table_names_plural = TRUE;

	// Database configuration
	protected $db = 'default';
	protected $_db;
	protected $db_applied = array();
	
	// Cache configuration
	protected $cache = 'default';
	protected $_cache;

	/**
	 * Creates and returns a new model.
	 *
	 * @chainable
	 * @param   string  model name
	 * @param   mixed   parameter for find()
	 * @return  ORM
	 */
	public static function factory($object_name, $id = NULL)
	{
		//$model = 'Model_'.ucfirst($object_name);
		$model = ucfirst($object_name) . '_Model';
		$model = new $model();

		// only models loaded on unique ID get cached
		if(!empty($id) && !is_object($id) && $model->cachable)
		{
			if($object = $model->cache->get( $model->cache_key($id) ))
			{
				echo "Looking for <b>{$model->object_name}</b> in cache on: " .  $model->cache_key($id) . "!<br>";

				if(is_object($object))
				{
					// Object found in cache
					if(!request::is_ajax()) echo "Found <b>{$model->object_name}</b> in cache on: " .  $model->cache_key($id) . "!<br>";
					return $object;
				}
				else
				{
					// Link found in cache
					return CORM::factory($object_name, $object);
				}
			}
		}

		// model is not cached
		if (is_object($id))
		{
			// Load an object
			$model->load_values((array) $id);
		}
		elseif (!empty($id))
		{
			// Find an object
			$model->find($id);
		}

		if($model->loaded && $model->cachable)
		{
			// cache object
			$model->cache();
		}

		return $model;
	}

	/**
	 * Prepares the model database connection and loads the object.
	 *
	 * @param   mixed  parameter for find or object to load
	 * @return  void
	 */
	public function __construct($id = NULL)
	{
		// Initialize database
		$this->__initialize();

		// Clear the object
		$this->clear();
	}

	/**
	 * Prepares the model database connection, determines the table name,
	 * and loads column information.
	 *
	 * @return  void
	 */
	public function __initialize()
	{
		// Set the object name and plural name
		//$this->object_name   = strtolower(substr(get_class($this), 6));
		$this->object_name   = strtolower(substr(get_class($this), 0, -6));
		$this->object_plural = inflector::plural($this->object_name);

		if (empty($this->table_name))
		{
			// Table name is the same as the object name
			$this->table_name = $this->object_name;

			if ($this->table_names_plural === TRUE)
			{
				// Make the table name plural
				$this->table_name = inflector::plural($this->table_name);
			}
		}

		if (is_array($this->ignored_columns))
		{
			// Make the ignored columns mirrored = mirrored
			$this->ignored_columns = array_combine($this->ignored_columns, $this->ignored_columns);
		}
		
		if( empty($this->cache_keys) )
		{
			// default to primary_key
			$this->cache_keys = array( $this->primary_key );
		}
		
		// lazy load db
		$this->_db = $this->db;
		unset($this->db);

		// lazy load cache
		$this->_cache = $this->cache;
		unset($this->cache);
	}

	// Cache the object
	protected function cache($additional = TRUE)
	{
		foreach($this->cache_keys as $seq => $key)
		{
			if(! $additional && $seq > 0)
				return;
			
			// only cache on keys that are set
			if(!isset($this->$key))
				continue;
			
			$key = $this->cache_key($this->object[$key]);
			$value = $seq === 0 ? $this : $this->object[$this->cache_keys[0]];
			
			$this->cache->set( $key , $value , NULL, $this->expire);
			
			if(!request::is_ajax()) echo "Writing <b>{$this->object_name}</b> to cache! (key: $key)<br>";
		}
	}

	// Generate cache key - note some models have overloaded this method
	public function cache_key($key)
	{
		return 'CORM::' . $this->object_name . '::' . $key;
	}
	
	// Delete object from cache
	protected function cache_delete()
	{
		foreach($this->cache_keys as $key)
		{
			$this->cache->delete( $this->cache_key( $this->object[$key] ) );
			if(!request::is_ajax()) echo "Removing <b>{$this->object_name}</b> from cache! (key: $key)<br>";
		}
	}

	protected function cache_relation_key($column,$id = NULL)
	{
		$object_name  = in_array($column,$this->has_many) || in_array($column,$this->has_and_belongs_to_many) ? inflector::singular($column) : $column;
		return in_array($column,$this->belongs_to) 
			? 'CORM::' . $object_name . '::' . $this->object[$this->foreign_key($object_name)] . '::' . $this->object_name
			: 'CORM::' . $this->object_name . '::' . ($id !== NULL ? $id : $this->primary_key_value) . '::' . $object_name;
	}

	/**
	 * Allows serialization of only the object data and state, to prevent
	 * "stale" objects being unserialized, which also requires less memory.
	 *
	 * @return  array
	 */
	public function __sleep()
	{
		// Store only information about the object
		return array('object_name', 'object', 'changed', 'loaded', 'saved');
	}

	/**
	 * Prepares the database connection and reloads the object.
	 *
	 * @return  void
	 */
	public function __wakeup()
	{
		// Initialize
		$this->__initialize();
	}

	/**
	 * Handles retrieval of all model values, relationships, and metadata.
	 *
	 * @param   string  column name
	 * @return  mixed
	 */
	public function __get($column)
	{
		if (array_key_exists($column, $this->object))
		{
			return $this->object[$column];
		}
		elseif (isset($this->related[$column]))
		{
			return $this->related[$column];
		}
		elseif ($column === 'primary_key_value')
		{
			return $this->object[$this->primary_key];
		}
		elseif ($column === 'db')
		{
			// lazy loading of DB
			if(!is_object($this->_db))
			{
				$this->_db = Database::instance($this->_db);
			}

			return $this->_db;
		}
		elseif ($column === 'cache')
		{
			// lazy loading of cache
			if(!is_object($this->_cache))
			{
				$this->_cache = MCache::instance($this->_cache);
			}
			
			return $this->_cache;
		}
		elseif (isset($this->sets[$column]))
		{
			// custom set
			$object_name = $this->sets[$column]['object_name'];
			$id_set = $this->get_relations($column);
			// custom sets are stored in the related array
			//return $this->related[$column] = $this->get_set($object_name,$id_set);
			return $this->related[$column] = new CORM_Iterator($object_name,$id_set);
		}
		elseif (in_array($column,$this->has_one) || in_array($column,$this->belongs_to) || in_array($column,$this->has_many) || in_array($column,$this->has_and_belongs_to_many))
		{
			// relationship
			$object_name  = in_array($column,$this->has_many) || in_array($column,$this->has_and_belongs_to_many) ? inflector::singular($column) : $column;
			$id_set = $this->get_relations($column);
			//return $this->related[$column] = $this->get_set($object_name,$id_set);
			return $this->related[$column] = is_array($id_set) ? new CORM_Iterator($object_name,$id_set) : CORM::factory($object_name,$id_set);
		}
		elseif (isset($this->ignored_columns[$column]))
		{
			return NULL;
		}
		elseif (in_array($column, array
			(
				'object_name', 'object_plural', // Object
				'primary_key', 'primary_val', 'table_name', 'table_columns', // Table
				'loaded', 'saved', // Status
				'has_one', 'belongs_to', 'has_many', 'has_and_belongs_to_many', 'load_with', // Relationships
				'cachable','expire' // Cache data
			)))
		{
			// Model meta information
			return $this->$column;
		}
		else
		{
			throw new Kohana_Exception('core.invalid_property', $column, get_class($this));
		}
	}

	// Get relation ID set
	public function get_relations($column)
	{
		if(isset($this->relations[$column]))
		{
			return $this->relations[$column];
		}
		elseif (in_array($column,$this->belongs_to))
		{
			// we don't cache the belongs_to relation
			// it is stored in the object itself
			// however, if the parent (has_one/has_many) object
			// requests this one, then the relation is cached
			// so when this object is deleted, it has to clear
			// all belongs_to based relations from the cache
			return $this->object[$this->foreign_key($column)];
		}

		$relation_key = $this->cache_relation_key($column);

		// try cache
		if(($relation_value = $this->cache->get($relation_key)) === NULL)
		{
			// no relation found in cache
			if(in_array($column,$this->has_one))
			{
				// one<>one relationship - find ID
				$model = CORM::factory($column);

				$relation_value = $this->db
					->select($model->primary_key)
					->where($this->foreign_key($column, $model->table_name), $this->object[$this->primary_key])
					->get($model->table_name)
					->result(FALSE)
					->current();

				$relation_value = $relation_value[$model->primary_key];
			}
			else 
			{
				if(isset($this->sets[$column]))
				{
					// user defined set - find ID (set)
					$model = CORM::factory($this->sets[$column]['object_name']);
					$table_name = $model->table_name;

					// prepare DB
					$this->db
						->select($model->primary_key);
					
					foreach($this->sets[$column]['methods'] as $method => $args)
					{
						switch(count($args))
						{
							case 1: $this->db->$method($args[0]); break;
							case 2: $this->db->$method($args[0],$args[1]); break;
							case 3: $this->db->$method($args[0],$args[1],$args[2]); break;
						}
					}
				}
				else
				{
					$model = CORM::factory(inflector::singular($column));
					$table_name = $model->table_name;
	
					if(in_array($column,$this->has_many))
					{
						// one<>many relationship - find ID set
						$this->db
							->select($model->primary_key)
							->where($this->foreign_key($column, $model->table_name), $this->object[$this->primary_key]);
					}
					else
					{
						// many<>many relationship - find ID set
						$table_name = $model->join_table($this->table_name);
	
						$this->db
							->select($model->foreign_key(NULL).' AS ' . $model->primary_key)
							->where($this->foreign_key(NULL, $table_name), $this->object[$this->primary_key]);
					}
				}

				if(! isset($this->sets[$column]) || ! isset($this->sets[$column]['methods']['orderby']))
				{
					// order results by ID ASC
					$this->db->orderby( in_array($column,$this->has_and_belongs_to_many) ? $model->foreign_key(NULL) : $this->primary_key,'ASC');
				}

				$id_set = $this->db->get($table_name)->result(FALSE);

				$relation_value = array();

				foreach($id_set as $row)
				{
					$relation_value[] = $row[$model->primary_key];
				}
			}

			//$this->db->pop();

			echo 'Writing relation for <b>' . $this->object_name . ':' . $column . '</b> to cache<br>';

			// cache relation
			$this->cache->set($relation_key,$relation_value);
		}
		else
			echo 'Found relation for <b>' . $this->object_name . ':' . $column . '</b> in cache<br>';

		return $this->relations[$column] = $relation_value;
	}

	// Clear relation data from object & cache
	public function clear_relations($columns,array $id_set = array())
	{
		if( ! is_array($columns) )
			$columns = array($columns);

		if($this->empty_primary_key() && $id_set === NULL)
			return;

		foreach($columns as $column)
		{
			unset($this->relations[$column],$this->related[$column]);

			if(count($id_set))
			{
				// force IDs
				foreach($id_set as $id)
				{
					echo 'clearing ' . $this->cache_relation_key($column,$id) . '<br>';
					$this->cache->delete( $this->cache_relation_key($column,$id) );
				}
			}
			else
			{
				echo 'clearing ' . $this->cache_relation_key($column) . '<br>';
				$this->cache->delete( $this->cache_relation_key($column) );
			}
		}
	}

	/**
	 * Handles setting of all model values, and tracks changes between values.
	 *
	 * @param   string  column name
	 * @param   mixed   column value
	 * @return  void
	 */
	public function __set($column, $value)
	{
		if (isset($this->ignored_columns[$column]))
		{
			return NULL;
		}
		elseif (isset($this->object[$column]) OR array_key_exists($column, $this->object))
		{
			if (isset($this->table_columns[$column]))
			{
				// Data has changed
				$this->changed[$column] = $column;

				// Object is no longer saved
				$this->saved = FALSE;
			}

			$this->object[$column] = $this->load_type($column, $value);
		}
		else
		{
			throw new Kohana_Exception('core.invalid_property', $column, get_class($this));
		}
	}

	/**
	 * Checks if object data is set.
	 *
	 * @param   string  column name
	 * @return  boolean
	 */
	public function __isset($column)
	{
		return (isset($this->object[$column]) OR isset($this->related[$column]));
	}

	/**
	 * Unsets object data.
	 *
	 * @param   string  column name
	 * @return  void
	 */
	public function __unset($column)
	{
		unset($this->object[$column], $this->changed[$column], $this->related[$column]);
	}

	/**
	 * Displays the primary key of a model when it is converted to a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->object_name;
		//return (string) $this->object[$this->primary_key];
	}

	/**
	 * Returns the values of this object as an array.
	 *
	 * @return  array
	 */
	public function as_array()
	{
		$object = array();

		foreach ($this->object as $key => $val)
		{
			// Reconstruct the array (calls __get)
			$object[$key] = $this->$key;
		}

		return $object;
	}

	/**
	 * Validates the current object. This method should generally be called
	 * via the model, after the $_POST Validation object has been created.
	 *
	 * @param   object   Validation array
	 * @return  boolean
	 */
	public function validate(Validation $array, $save = FALSE)
	{
		$safe_array = $array->safe_array();

		if ( ! $array->submitted())
		{
			foreach ($safe_array as $key => $value)
			{
				// Get the value from this object
				$value = $this->$key;

				/*if (is_object($value) AND $value instanceof CORM_Iterator)
				{
					// Convert the value to an array of primary keys
					$value = $value->primary_key_array();
				}*/

				// Pre-fill data
				$array[$key] = $value;
			}
		}

		// Validate the array
		if ($status = $array->validate())
		{
			// Grab only set fields (excludes missing data, unlike safe_array)
			$fields = $array->as_array();

			foreach ($fields as $key => $value)
			{
				if (isset($safe_array[$key]))
				{
					// Set new data, ignoring any missing fields or fields without rules
					$this->$key = $value;
				}
			}

			if ($save === TRUE OR is_string($save))
			{
				// Save this object
				$this->save();

				if (is_string($save))
				{
					// Redirect to the saved page
					url::redirect($save);
				}
			}
		}

		// Return validation status
		return $status;
	}

	/**
	 * Saves the current object.
	 *
	 * @chainable
	 * @return  ORM
	 */
	public function save()
	{
		$update_cache     = $this->cachable && ! empty($this->changed);
		$cache_additional = $update_cache && !$this->loaded;

		if( ! $this->loaded && ! empty($this->changed) )
		{
			// New object - delete all belongs_to based keys
			// The parent model's has_one/many relations to this object will be incorrect when this object is saved
			$this->clear_relations($this->belongs_to);
		}
		elseif ($this->loaded)
		{
			foreach($this->belongs_to as $column)
			{
				if(isset($this->changed[ $this->foreign_key($column) ]))
				{
					// 2 relations change - the old parent and the new parent
					die('change of parent not yet supported');
				}
			}
		}

		if ( ! empty($this->changed))
		{
			$data = array();
			foreach ($this->changed as $column)
			{
				// Compile changed data
				$data[$column] = $this->object[$column];
			}

			if ($this->loaded === TRUE)
			{
				$query = $this->db
					->where($this->primary_key, $this->object[$this->primary_key])
					->update($this->table_name, $data);

				// Object has been saved
				$this->saved = TRUE;
			}
			else
			{
				$query = $this->db
					->insert($this->table_name, $data);

				if ($query->count() > 0)
				{
					if (empty($this->object[$this->primary_key]))
					{
						// Load the insert id as the primary key
						$this->object[$this->primary_key] = $query->insert_id();
					}

					// Object is now loaded and saved
					$this->loaded = $this->saved = TRUE;
				}
			}

			if ($this->saved === TRUE)
			{
				// All changes have been saved
				$this->changed = array();
			}
		}

		if($update_cache)
		{
			$this->cache($cache_additional);
		}

		return $this;
	}

	/**
	 * Deletes the current object from the database. This does NOT destroy
	 * relationships that have been created with other objects.
	 *
	 * @chainable
	 * @return  ORM
	 */
	public function delete($id = NULL)
	{
		if($this->cachable)
		{
			if ($id === NULL AND $this->loaded)
			{
				// Use the the primary key value
				$id = $this->object[$this->primary_key];
			}
			$this->cache_delete();
		}

		// habtm relations need to be cleared both ways
		foreach($this->has_and_belongs_to_many as $column)
		{
			$id_set = $this->get_relations($column);
			
			if(count($id_set))
				CORM::factory(inflector::singular($column))->clear_relations($this->object_plural,$id_set);
		}

		// delete all cached relations
		$this->clear_relations( array_merge($this->has_one,$this->belongs_to,$this->has_many,$this->has_and_belongs_to_many) );

		// Delete this object
		$this->db->where($this->primary_key, $id)->delete($this->table_name);

		return $this->clear();
	}

	/**
	 * Tests if this object has a relationship to a different model.
	 *
	 * @param   object   related ORM model
	 * @param   boolean  check for any relations to given model
	 * @return  boolean
	 */
	public function has(CORM $model, $any = FALSE)
	{
		$id_set = $this->get_relations($model->object_plural);

		if( ! $model->empty_primary_key())
		{
			return in_array($model->primary_key_value, $id_set);
		}
		else
		{
			return ! empty($id_set);
		}
	}

	/**
	 * Adds a new relationship to between this model and another.
	 *
	 * @param   object   related ORM model
	 * @return  boolean
	 */
	public function add($models)
	{
		if( !is_array($models) )
			$models = array($models);

		// Get the faked column name
		$column = current($models)->object_plural;

		$added_ids = array();

		foreach($models as $model)
		{
			// already added
			if($this->has($model) || $model->empty_primary_key())
				continue;

			if($column !== $model->object_plural)
				die('only add objects of the same type');

			$added_ids[] = $model->primary_key_value;

			// clear models relations
			$model->clear_relations($model->has_and_belongs_to_many);
		}

		if(count($added_ids))
		{
			$this->clear_relations($column);

			// join table
			$join_table = $model->join_table($this->table_name);

			// Foreign keys for the join table
			$object_fk  = $this->foreign_key(NULL);
			$related_fk = $model->foreign_key(NULL);

			foreach ($added_ids as $id)
			{
				// Insert the new relationship
				$this->db->insert($join_table, array
				(
					$object_fk  => $this->object[$this->primary_key],
					$related_fk => $id,
				));
			}
		}

		return count($added_ids) > 0;
	}

	/**
	 * Adds a new relationship to between this model and another.
	 *
	 * @param   object   related ORM model
	 * @return  boolean
	 */
	public function remove($models)
	{
		if( !is_array($models) )
			$models = array($models);

		// Get the faked column name
		$column = current($models)->object_plural;

		$removed_ids = array();

		foreach($models as $model)
		{
			// wasn't added
			if( ! $this->has($model) || $model->empty_primary_key())
				continue;

			$removed_ids[] = $model->primary_key_value;

			// clear models relations
			$model->clear_relations($model->has_and_belongs_to_many);
		}

		if(count($removed_ids))
		{
			$this->clear_relations($column);

			// join table
			$join_table = $model->join_table($this->table_name);

			// Foreign keys for the join table
			$object_fk  = $this->foreign_key(NULL);
			$related_fk = $model->foreign_key(NULL);

			$this->db
				->where($object_fk, $this->object[$this->primary_key])
				->in($related_fk, $removed_ids)
				->delete($join_table);
		}

		return count($removed_ids) > 0;
	}

	/**
	 * Unloads the current object and clears the status.
	 *
	 * @chainable
	 * @return  ORM
	 */
	public function clear()
	{
		$this->related = array();
		$this->relations = array();

		// Create an array with all the columns set to NULL
		$columns = array_keys($this->table_columns);
		$values  = array_combine($columns, array_fill(0, count($columns), NULL));

		// Replace the current object with an empty one
		$this->load_values($values);

		return $this;
	}

	/**
	 * Reloads the current object from the database.
	 *
	 * @chainable
	 * @return  ORM
	 */
	public function reload()
	{
		return $this->find($this->object[$this->primary_key]);
	}

	/**
	 * Finds and loads a single database row into the object.
	 *
	 * @chainable
	 * @param   primary key
	 * @return  ORM
	 */
	public function find($id = NULL)
	{
		$result = $this->db
			->where($this->table_name.'.'.$this->unique_key($id), $id)
			->get($this->table_name);

		if ($result->count() === 1)
		{
			// Load object values
			$this->load_values($result->result(FALSE)->current());
		}
		else
		{
			// Clear the object, nothing was found
			$this->clear();
		}
	}

	/**
	 * Loads an array of values into into the current object.
	 *
	 * @chainable
	 * @param   array  values to load
	 * @return  ORM
	 */
	public function load_values(array $values)
	{
		if (array_key_exists($this->primary_key, $values))
		{
			// Replace the object and reset the object status
			$this->object = $this->changed = $this->related = array();

			// Set the loaded and saved object status based on the primary key
			$this->loaded = $this->saved = ($values[$this->primary_key] !== NULL);
		}

		foreach ($values as $column => $value)
		{
			if (isset($this->table_columns[$column]))
			{
				// The type of the value can be determined, convert the value
				$value = $this->load_type($column, $value);
			}

			$this->object[$column] = $value;
		}

		return $this;
	}

	/**
	 * Loads a value according to the types defined by the column metadata.
	 *
	 * @param   string  column name
	 * @param   mixed   value to load
	 * @return  mixed
	 */
	protected function load_type($column, $value)
	{
		$type = gettype($value);
		if ($type == 'object' OR $type == 'array' OR ! isset($this->table_columns[$column]))
			return $value;

		// Load column data
		$column = $this->table_columns[$column];

		if ($value === NULL AND ! empty($column['null']))
			return $value;

		if ( ! empty($column['binary']) AND ! empty($column['exact']) AND (int) $column['length'] === 1)
		{
			// Use boolean for BINARY(1) fields
			$column['type'] = 'boolean';
		}

		switch ($column['type'])
		{
			case 'int':
				if ($value === '' AND ! empty($column['null']))
				{
					// Forms will only submit strings, so empty integer values must be null
					$value = NULL;
				}
				elseif ((float) $value > PHP_INT_MAX)
				{
					// This number cannot be represented by a PHP integer, so we convert it to a string
					$value = (string) $value;
				}
				else
				{
					$value = (int) $value;
				}
			break;
			case 'float':
				$value = (float) $value;
			break;
			case 'boolean':
				$value = (bool) $value;
			break;
			case 'string':
				$value = (string) $value;
			break;
		}

		return $value;
	}

	/**
	 * Returns the unique key for a specific value. This method is expected
	 * to be overloaded in models if the model has other unique columns.
	 *
	 * @param   mixed   unique value
	 * @return  string
	 */
	public function unique_key($id)
	{
		return $this->primary_key;
	}

	/**
	 * Determines the name of a foreign key for a specific table.
	 *
	 * @param   string  related table name
	 * @param   string  prefix table name (used for JOINs)
	 * @return  string
	 */
	public function foreign_key($table = NULL, $prefix_table = NULL)
	{
		if ($table === TRUE)
		{
			if (is_string($prefix_table))
			{
				// Use prefix table name and this table's PK
				return $prefix_table.'.'.$this->primary_key;
			}
			else
			{
				// Return the name of this table's PK
				return $this->table_name.'.'.$this->primary_key;
			}
		}

		if (is_string($prefix_table))
		{
			// Add a period for prefix_table.column support
			$prefix_table .= '.';
		}

		if (isset($this->foreign_key[$table]))
		{
			// Use the defined foreign key name, no magic here!
			$foreign_key = $this->foreign_key[$table];
		}
		else
		{
			if ( ! is_string($table) OR ! array_key_exists($table.'_'.$this->primary_key, $this->object))
			{
				// Use this table
				$table = $this->table_name;

				if (strpos($table, '.') !== FALSE)
				{
					// Hack around support for PostgreSQL schemas
					list ($schema, $table) = explode('.', $table, 2);
				}

				if ($this->table_names_plural === TRUE)
				{
					// Make the key name singular
					$table = inflector::singular($table);
				}
			}

			$foreign_key = $table.'_'.$this->primary_key;
		}

		return $prefix_table.$foreign_key;
	}

	/**
	 * This uses alphabetical comparison to choose the name of the table.
	 *
	 * Example: The joining table of users and roles would be roles_users,
	 * because "r" comes before "u". Joining products and categories would
	 * result in categories_products, because "c" comes before "p".
	 *
	 * Example: zoo > zebra > robber > ocean > angel > aardvark
	 *
	 * @param   string  table name
	 * @return  string
	 */
	public function join_table($table)
	{
		if ($this->table_name > $table)
		{
			$table = $table.'_'.$this->table_name;
		}
		else
		{
			$table = $this->table_name.'_'.$table;
		}

		return $table;
	}

	/**
	 * Returns whether or not primary key is empty
	 *
	 * @return bool
	 */
	protected function empty_primary_key()
	{
		return (empty($this->object[$this->primary_key]) AND $this->object[$this->primary_key] !== '0');
	}

	/**
	 * Finds multiple database rows and returns an iterator of the rows found.
	 *
	 * @chainable
	 * @param   integer  SQL limit
	 * @param   integer  SQL offset
	 * @return  ORM_Iterator
	 */
	/*public function find_all($limit = NULL, $offset = NULL)
	{
		if ($limit !== NULL AND ! isset($this->db_applied['limit']))
		{
			// Set limit
			$this->limit($limit);
		}

		if ($offset !== NULL AND ! isset($this->db_applied['offset']))
		{
			// Set offset
			$this->offset($offset);
		}

		return $this->load_result(TRUE);
	}*/

	/**
	 * Handles pass-through to database methods. Calls to query methods
	 * (query, get, insert, update) are not allowed. Query builder methods
	 * are chainable.
	 *
	 * @param   string  method name
	 * @param   array   method arguments
	 * @return  mixed
	 */
	/*public function __call($method, array $args)
	{
		if (method_exists($this->db, $method))
		{
			if (in_array($method, array('query', 'get', 'insert', 'update', 'delete')))
				throw new Kohana_Exception('orm.query_methods_not_allowed');

			// Method has been applied to the database
			$this->db_applied[$method] = $method;

			// Number of arguments passed
			$num_args = count($args);

			if ($method === 'select' AND $num_args > 3)
			{
				// Call select() manually to avoid call_user_func_array
				$this->db->select($args);
			}
			else
			{
				// We use switch here to manually call the database methods. This is
				// done for speed: call_user_func_array can take over 300% longer to
				// make calls. Most database methods are 4 arguments or less, so this
				// avoids almost any calls to call_user_func_array.

				switch ($num_args)
				{
					case 0:
						if (in_array($method, array('open_paren', 'close_paren', 'enable_cache', 'disable_cache')))
						{
							// Should return ORM, not Database
							$this->db->$method();
						}
						else
						{
							// Support for things like reset_select, reset_write, list_tables
							return $this->db->$method();
						}
					break;
					case 1:
						$this->db->$method($args[0]);
					break;
					case 2:
						$this->db->$method($args[0], $args[1]);
					break;
					case 3:
						$this->db->$method($args[0], $args[1], $args[2]);
					break;
					case 4:
						$this->db->$method($args[0], $args[1], $args[2], $args[3]);
					break;
					default:
						// Here comes the snail...
						call_user_func_array(array($this->db, $method), $args);
					break;
				}
			}

			return $this;
		}
		else
		{
			throw new Kohana_Exception('core.invalid_method', $method, get_class($this));
		}
	}*/

	/**
	 * Loads a database result, either as a new object for this model, or as
	 * an iterator for multiple rows.
	 *
	 * @chainable
	 * @param   boolean       return an iterator or load a single row
	 * @return  ORM           for single rows
	 * @return  ORM_Iterator  for multiple rows
	 */
	/*protected function load_result($array = FALSE)
	{
		if ($array === FALSE)
		{
			// Only fetch 1 record
			$this->db->limit(1);
		}

		if ( ! isset($this->db_applied['select']))
		{
			// Select all columns by default
			$this->db->select($this->table_name.'.*');
		}

		if ( ! isset($this->db_applied['orderby']) AND ! empty($this->sorting))
		{
			$sorting = array();
			foreach ($this->sorting as $column => $direction)
			{
				if (strpos($column, '.') === FALSE)
				{
					// Keeps sorting working properly when using JOINs on
					// tables with columns of the same name
					$column = $this->table_name.'.'.$column;
				}

				$sorting[$column] = $direction;
			}

			// Apply the user-defined sorting
			$this->db->orderby($sorting);
		}

		// Load the result
		$result = $this->db->get($this->table_name);

		if ($array === TRUE)
		{
			// Return an iterated result
			return new ORM_Iterator($this, $result);
		}

		if ($result->count() === 1)
		{
			// Load object values
			$this->load_values($result->result(FALSE)->current());
		}
		else
		{
			// Clear the object, nothing was found
			$this->clear();
		}

		return $this;
	}*/

} // End ORM

