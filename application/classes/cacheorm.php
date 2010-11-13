<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Caching Wrapper around core ORM functionality
 */
class CacheORM extends Kohana_ORM {

	// Cache configuration
	protected static $cache = 'memcache';
	protected $_cache;

    // used for building/invalidating cached instances; key should look like [model_name]_[model_cache_key]_[model_primary_key] ie: event_12_100
    private $_model_cache_key = NULL; 

	/**
	 * Creates and returns a new model.
	 *
	 * @chainable
	 * @param   string  model name
	 * @param   mixed   parameter for find()
	 * @return  ORM
	 */
	public static function factory($model, $id = NULL)
	{
        $object = NULL;

        $cache = Cache::instance(CacheORM::$cache);

        // $this->_model_cache_key = $cache->get($model.'_key');

        Kohana::$log->add('CacheORM::factory() --> attempting to retrieve _model_cache_key from cache', $model.'_key');

        $_model_cache_key = $cache->get($model.'_key');

        Kohana::$log->add('CacheORM::factory() --> _model_cache_key', $_model_cache_key);

        if($_model_cache_key === NULL)
        {
            $_model_cache_key = 1;
            $cache->set($model.'_key', $_model_cache_key);
        }
        
        // look for cached model because $id is specified 
        if($id !== NULL)
        {
            Kohana::$log->add('CacheORM::factory() --> attempting to retrieve object from cache', $model.'_'.$_model_cache_key.'_'.$id);
            $object = $cache->get($model.'_'.$_model_cache_key.'_'.$id); // @TODO: build cache key in helper function
        }

        if($object === NULL)
        {
            $object = parent::factory($model, $id);
            Kohana::$log->add('CacheORM::factory() -->', 'returning '.$object->_object_name.' from ORM parent class');
        }
        else
        {
            Kohana::$log->add('CacheORM::factory() -->', 'returning '.$object->_object_name.' from cache');
        }

        $object->_model_cache_key = $_model_cache_key;

        if($id !== NULL)
        {
            $cache->set($object->_object_name.'_'.$object->_model_cache_key.'_'.$object->id, $object); // @TODO: build cache key in helper function
        }

        Kohana::$log->add('CacheORM::factory() --> storing _model_cache_key in object instance', $_model_cache_key);

        return $object;
        
	}

    /**
     * Saves model and puts cached model object into cache
     * @TODO: increment $this->_model_cache_key... or should there be a separate cache key for multiple rows, like $this->_model_cache_key_multiple?
     */
    public function save()
    {
        $model = parent::save();

        // increment model cache key to invalidate all cached values related to this model
        // @TODO: this may not be efficient. should investigate more efficient way of doing this


        $cache = Cache::instance(CacheORM::$cache);

        Kohana::$log->add('CacheORM::factory() --> $this->_model_cache_key', $this->_model_cache_key);

        $this->_model_cache_key = (int)$this->_model_cache_key + 1; 
        $cache->set($this->_object_name.'_key', $this->_model_cache_key);

        Kohana::$log->add('CacheORM::factory() --> $this->_model_cache_key', $this->_model_cache_key);

        Kohana::$log->add('CacheORM::factory() --> setting object in cache', $this->_object_name.'_'.$this->_model_cache_key.'_'.$model->id);
        $cache->set($this->_object_name.'_'.$this->_model_cache_key.'_'.$model->id, $model); // @TODO: build cache key in helper function

        return $model;
    }

    /**
     * Deletes the model and increments the model cache key
     */
    public function delete($id = NULL)
    {
        $model = parent::delete($id);

        // increment model cache key to invalidate all cached values related to this model
        // @TODO: this may not be efficient. should investigate more efficient way of doing this

        $this->_model_cache_key = $this->_model_cache_key + 1; 

        return $model;
    }

    /**
     * Deletes all and increments model cache key
     */
    public function delete_all()
    {
        $model = parent::delete_all();

        // increment model cache key to invalidate all cached values related to this model
        // @TODO: this may not be efficient. should investigate more efficient way of doing this

        $this->_model_cache_key = $this->_model_cache_key + 1; 

        return $model;
    }

	/**
	 * Handles retrieval of all model values, relationships, and metadata.
	 *
	 * @param   string  column name
	 * @return  mixed
	 */
	public function __get($column)
	{
		if (array_key_exists($column, $this->_object))
		{
			$this->_load();

			return $this->_object[$column];
		}
		elseif (isset($this->_related[$column]) AND $this->_related[$column]->_loaded)
		{
			// Return related model that has already been loaded
			return $this->_related[$column];
		}
		elseif (isset($this->_belongs_to[$column]))
		{
			$this->_load();

			$model = $this->_related($column);

			// Use this model's column and foreign model's primary key
			$col = $model->_table_name.'.'.$model->_primary_key;
			$val = $this->_object[$this->_belongs_to[$column]['foreign_key']];

			$model->where($col, '=', $val)->find();

			return $this->_related[$column] = $model;
		}
		elseif (isset($this->_has_one[$column]))
		{
			$model = $this->_related($column);

			// Use this model's primary key value and foreign model's column
			$col = $model->_table_name.'.'.$this->_has_one[$column]['foreign_key'];
			$val = $this->pk();

			$model->where($col, '=', $val)->find();

			return $this->_related[$column] = $model;
		}
		elseif (isset($this->_has_many[$column]))
		{
			$model = CacheORM::factory($this->_has_many[$column]['model']);

			if (isset($this->_has_many[$column]['through']))
			{
				// Grab has_many "through" relationship table
				$through = $this->_has_many[$column]['through'];

				// Join on through model's target foreign key (far_key) and target model's primary key
				$join_col1 = $through.'.'.$this->_has_many[$column]['far_key'];
				$join_col2 = $model->_table_name.'.'.$model->_primary_key;

				$model->join($through)->on($join_col1, '=', $join_col2);

				// Through table's source foreign key (foreign_key) should be this model's primary key
				$col = $through.'.'.$this->_has_many[$column]['foreign_key'];
				$val = $this->pk();
			}
			else
			{
				// Simple has_many relationship, search where target model's foreign key is this model's primary key
				$col = $model->_table_name.'.'.$this->_has_many[$column]['foreign_key'];
				$val = $this->pk();
			}

			return $model->where($col, '=', $val);
		}
		else
		{
			throw new Kohana_Exception('The :property property does not exist in the :class class',
				array(':property' => $column, ':class' => get_class($this)));
		}
	}

	/**
	 * Returns an ORM model for the given one-one related alias
	 *
	 * @param   string  alias name
	 * @return  ORM
	 */
	protected function _related($alias)
	{
		if (isset($this->_related[$alias]))
		{
			return $this->_related[$alias];
		}
		elseif (isset($this->_has_one[$alias]))
		{
			return $this->_related[$alias] = CacheORM::factory($this->_has_one[$alias]['model']);
		}
		elseif (isset($this->_belongs_to[$alias]))
		{
			return $this->_related[$alias] = CacheORM::factory($this->_belongs_to[$alias]['model']);
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Loads a database result, either as a new object for this model, or as
	 * an iterator for multiple rows.
	 *
	 * @chainable
	 * @param   boolean       return an iterator or load a single row
	 * @return  ORM           for single rows
	 * @return  ORM_Iterator  for multiple rows
	 */
	protected function _load_result($multiple = FALSE)
	{
		$this->_db_builder->from($this->_table_name);

		if ($multiple === FALSE)
		{
			// Only fetch 1 record
			$this->_db_builder->limit(1);
		}

		// Select all columns by default
		$this->_db_builder->select($this->_table_name.'.*');

		if ( ! isset($this->_db_applied['order_by']) AND ! empty($this->_sorting))
		{
			foreach ($this->_sorting as $column => $direction)
			{
				if (strpos($column, '.') === FALSE)
				{
					// Sorting column for use in JOINs
					$column = $this->_table_name.'.'.$column;
				}

				$this->_db_builder->order_by($column, $direction);
			}
		}

		if ($multiple === TRUE)
		{
            // Select all columns by default
            $this->_db_builder->select($this->_table_name.'.'.$this->primary_key());

			// Return database iterator casting to this object type
			// $result = $this->_db_builder->as_object(get_class($this))->execute($this->_db);
			$query = $this->_db_builder;
            Kohana::$log->add('CacheORM->_load_results() SQL', $query);
            $query_hash = md5($query);
            Kohana::$log->add('CacheORM->_load_results() SQL md5', $query_hash);
            Kohana::$log->add('CacheORM->_load_results() SQL md5', $this->_object_name.'_'.$this->_model_cache_key.'_'.$query_hash);

            $result = $query->as_object(get_class($this))->execute($this->_db);

            // print_r($result);

            $result_ids = array();
            foreach($result as $row)
            {
                $result_ids[$row->id] = $this->_object_name.'_'.$this->_model_cache_key.'_'.$row->id;
            }
            // print_r($result_ids);

            $results = Cache::instance(CacheORM::$cache)->get(array_values($result_ids));

            // print_r($results);

            $sanitized_results = array();

            foreach($results as $key => $object)
            {
                if($object === NULL)
                {
                    $id = explode('_', $key);
                    $id = array_pop($id);
                    Kohana::$log->add('CacheORM->_load_results() no object in cache for id', $id);
                    $results[$key] = CacheORM::factory($this->_object_name, $id);
                }
            }

            // print_r($results);

            Kohana::$log->add('CacheORM->_load_results()', 'returning '.$this->_object_plural.' from cache via multiget');
            return $results;

			// $this->reset();

			// return $result;
		}
		else
		{
			// // Load the result as an associative array
			// $query = $this->_db_builder;
            // Kohana::$log->add('CacheORM->_load_results() SQL', $query);
            // $query_hash = md5($query);
            // Kohana::$log->add('CacheORM->_load_results() SQL md5', $query_hash);
            // Kohana::$log->add('CacheORM->_load_results() SQL md5', $this->_object_name.'_'.$this->_model_cache_key.'_'.$query_hash);

			// $this->reset();

			// if ($result->count() === 1)
			// {
			// 	// Load object values
			// 	$this->_load_values($result->current());
			// }
			// else
			// {
			// 	// Clear the object, nothing was found
			// 	$this->clear();
			// }

			// return $this;

            return parent::_load_result($multiple);
		}

	}
} // End ORM
