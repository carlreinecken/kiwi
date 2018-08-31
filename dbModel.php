<?php

abstract class dbModel {

    protected $_db;
    protected $_clause;
    protected $_last_query;
    protected $_primary_key;
    protected $_table;

    public function __construct($db)
    {
        $this->_db = $db;
        $this->_table = $this->config('table');
        $this->_primary_key = $this->config('primary_key');
    }
    
    /**
     * Cast the current object as array
     *
     * @return array
     */
    public function array()
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * Get all objects from the table with current query
     *
     * @param boolean Throw exception if no object is found
     * @throws \Exception No objects found
     * @return Self reference
     */
    public function all($throw = false)
    {
        $objects = [];
        $result = $this->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
           array_push($objects, (new $this($this->_db))->fill($row));
        }

        if (empty($objects) && $throw) {
            throw new \Exception(sprintf('No %s found', get_class($this)));
        }
        return $objects;
    }

    /**
     * Get first object with current query
     *
     * @param boolean Throw exception if no object is found
     * @throws \Exception No object found
     * @return Self reference
     */
    public function get($throw = false)
    {
        $results = $this->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$results && $throw) {
            throw new \Exception(sprintf('No %s found', get_class($this)));
        } else if (!$results) {
            $results = [];
        }

        $this->fill($results);

        return $this;
    }

    /**
     * Get first object by primary key
     *
     * @param Primary key
     * @param boolean Throw exception if no object is found
     * @throws \Exception No object found
     * @return Self reference
     */
    public function find($primary_key, $throw = false)
    {
        return $this
            ->reset_clause()
            ->where_primary_key($primary_key)
            ->get($throw);
    }

    /**
     * Insert current object into database and set new primary key
     *
     * @return Self reference
     */
    public function create()
    {
        $properties = $this->array();

        $keys = implode(',', array_keys($properties));
        $values = implode(',', array_values(array_map(
            array($this, 'quote'), $properties
        )));

        $result = $this->execute('INSERT INTO '.$this->_table.' ('.$keys.') VALUES ('.$values.')');
        
        if (!$result) {
            throw new \Exception('Error while creating %s', get_class($this));
        }

        $this->$primary_key = $this->_db->lastInsertRowID();

        return $this;
    }

    /**
     * Update object in database with current object
     *
     * @return Self reference
     */
    public function update()
    {
        $properties = $this->array();

        foreach ($properties as $key => $value) {
            $values[] = $key.' = '.$this->quote($value);
        }

        $this->where_primary_key();
        
        $result = $this->execute('UPDATE '.$this->_table.' SET '.implode(',', $values));

        if (!$result) {
            throw new \Exception('Error while updating %s', get_class($this));
        }

        return $this;
    }

    /**
     * Delete object in database with current object
     */
    public function delete()
    {
        $this->where_primary_key();
        
        $result = $this->execute('DELETE FROM '.$this->_table);
        
        if (!$result) {
            throw new \Exception('Error while deleting %s', get_class($this));
        }
    }

    /**
     * Set data of current object with array
     *
     * @param array $data Data to be filled
     * @return object Self reference
     */
    public function fill($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && !empty($value)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Append a where condition to the current query
     *
     * @param string $column_and_operator Logical operator, column and a comparison operator
     * @param any The value
     * @return Self reference
     */
    public function where($column_and_operator, $value)
    {
        $this->_clause .= (stripos($this->_clause, 'WHERE') === false)
            ? ' WHERE '
            : ' ';

        $this->_clause .= $column_and_operator.$this->quote($value);

        return $this;
    }
    
    /**
     * Append where condition for the primary key
     *
     * @param any Value for primary key
     * @return Self reference
     */
    protected function where_primary_key($value = null)
    {
        $primary_key = $this->_primary_key;
        $value = $value ?? $this->$primary_key;
        $this->where($primary_key.' = ', $value);
        return $this;
    }
    
    /**
     * Reset all where conditions
     *
     * @return Self reference
     */
    protected function reset_clause()
    {
        $this->_clause = '';
        return $this;
    }

    /**
     * Escape strings and set null values
     *
     * @param any The unprocessed value
     * @return any The processed value
     */
    protected function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_string($value)) {
            return '\''.$this->_db->escapeString($value).'\'';
        }
        return $value;
    }

    /**
     * Execute the current SQL with where conditions
     *
     * @param string Optional beginning of SQL query
     * @return The result of the database query
     */
    protected function execute($sql = null)
    {
        $query = ($sql) ? $sql : 'SELECT * FROM '.$this->_table;
        $result = $this->_db->query($query.$this->_clause);
        $this->_last_query = $query.$this->_clause;
        $this->reset_clause();
        return $result;
    }

    /**
     * Gets a config property set as static parameters in extended model.
     *
     * @param string $name Name of config property
     * @return string Contents of config property
     */
    protected function config($name)
    {
        $properties = (new ReflectionClass($this))->getStaticProperties();

        if ($properties && isset($properties[$name])) {
            return $properties[$name];
        }
        throw new \Exception(sprintf('No static %s set for %s model', $name, get_class($this)));

    }

    public function __toString()
    {
        return get_class($this).'('.$this->$_primary_key.')';
    }

}
