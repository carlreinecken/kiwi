<?php

/**
 * Kiwi: A simple abstract database model.
 *
 * @copyright Copyright (c) 2018, Carl Reinecken <carl@reinecken.net>
 */
abstract class Kiwi {

    protected $database;
    protected $conditions;
    protected $last_query;

    public function __construct($db)
    {
        $this->database = $db;
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
     * @param Boolean Throw exception if no object is found
     * @throws \Exception No objects found
     * @return Self reference
     */
    public function all($throw = false)
    {
        $objects = [];
        $result = $this->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
           array_push($objects, (new $this($this->database))->fill($row));
        }

        if (empty($objects) && $throw) {
            throw new \Exception(sprintf('No %s found', get_class($this)));
        }
        return $objects;
    }

    /**
     * Get first object with current query
     *
     * @param Boolean Throw exception if no object is found
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
     * @param Any Primary key
     * @param Boolean Throw exception if no object is found
     * @throws \Exception No object found
     * @return Object Self reference
     */
    public function find($primary_key, $throw = false)
    {
        return $this
            ->where_primary_key($primary_key)
            ->get($throw);
    }

    /**
     * Insert current object into database and set new primary key
     *
     * @throws \Exception Errow while creating
     * @return Object Self reference
     */
    public function create()
    {
        $properties = $this->array();

        $keys = implode(',', array_keys($properties));
        $values = implode(',', array_values(array_map(
            array($this, 'quote'), $properties
        )));

        $this->reset_conditions();
        $result = $this->execute('INSERT INTO '.static::$table.' ('.$keys.') VALUES ('.$values.')');

        if (!$result) {
            throw new \Exception('Error while creating %s', get_class($this));
        }

        $this->{static::$primary_key} = $this->database->lastInsertRowID();

        return $this;
    }

    /**
     * Update object in database with current object
     *
     * @throws \Exception Error while updating
     * @return Object Self reference
     */
    public function update()
    {
        $properties = $this->array();

        foreach ($properties as $key => $value) {
            $values[] = $key.' = '.$this->quote($value);
        }

        $this->where_primary_key();

        $result = $this->execute('UPDATE '.static::$table.' SET '.implode(',', $values));

        if (!$result) {
            throw new \Exception('Error while updating %s', get_class($this));
        }

        return $this;
    }

    /**
     * Delete object in database with current object
     * @throws \Exception Error while deleting
     */
    public function delete()
    {
        $this->where_primary_key();

        $result = $this->execute('DELETE FROM '.static::$table);

        if (!$result) {
            throw new \Exception('Error while deleting %s', get_class($this));
        }
    }

    /**
     * Set data of current object with array
     *
     * @param Array $data Data to be filled
     * @return Object Self reference
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

    public function get_last_query()
    {
        return $this->last_query;
    }

    /**
     * Append a where condition to the current query
     *
     * @param String $column_and_operator Logical operator, column and a comparison operator
     * @param Any The value
     * @return Object Self reference
     */
    public function where($column_and_operator, $value)
    {
        $this->conditions .= (stripos($this->conditions, 'WHERE') === false)
            ? ' WHERE '
            : ' ';

        $this->conditions .= $column_and_operator.$this->quote($value);

        return $this;
    }

    /**
     * Append where condition for the primary key
     *
     * @param Any Value for primary key
     * @return Object Self reference
     */
    protected function where_primary_key($value = null)
    {
        return $this
            ->reset_conditions()
            ->where(static::$primary_key.' = ', $value ?? $this->{static::$primary_key});
    }

    /**
     * Reset all where conditions
     *
     * @return Object Self reference
     */
    protected function reset_conditions()
    {
        $this->conditions = '';
        return $this;
    }

    /**
     * Escape strings and set null values
     *
     * @param Any The unprocessed value
     * @return Any The processed value
     */
    protected function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_string($value)) {
            return '\''.$this->database->escapeString($value).'\'';
        }
        return $value;
    }

    /**
     * Execute the current SQL with where conditions
     *
     * @param String Optional beginning of SQL query
     * @return Any The result of the database query
     */
    protected function execute($sql = null)
    {
        $query = ($sql) ? $sql : 'SELECT * FROM '.static::$table;
        $result = $this->database->query($query.$this->conditions);
        $this->last_query = $query.$this->conditions;
        $this->reset_conditions();
        return $result;
    }

    public function __toString()
    {
        return get_class($this).'('.$this->{static::$primary_key}.')';
    }

}
