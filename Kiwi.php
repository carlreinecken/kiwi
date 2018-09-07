<?php

/**
 * Kiwi: A simple abstract SQLite3 database model.
 *
 * @version 07-09-2018 v1
 * @copyright Copyright (c) 2018, Carl Reinecken <carl@reinecken.net>
 */
abstract class Kiwi {

    protected $database;
    protected $conditions;
    protected $last_query;

    public function __construct($db)
    {
        $this->init_mass_assignment();
        $this->database = $db;
    }

    /**
     * Cast the current object to an array
     *
     * @return Array
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
    public function all()
    {
        $objects = [];
        $result = $this->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
           array_push($objects, (new $this($this->database))->set($row));
        }

        return $objects;
    }

    /**
     * Get first object with current query
     *
     * @throws \Exception No object found
     * @return Object Self reference
     */
    public function first_or_fail()
    {
        $results = $this->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$results) {
            throw new \Exception(sprintf('No %s found', $this));
        }

        return $this->set($results);
    }

    /**
     * Get first object with current query
     *
     * @return Object Self reference
     */
    public function first()
    {
        try {
            $this->first_or_fail();
        } catch (\Exception $e) {}

        return $this;
    }

    /**
     * Get first object by primary key
     *
     * @param Any Primary key
     * @return Object Self reference
     */
    public function find($primary_key)
    {
        $this->where_primary_key($primary_key);
        return $this->first();
    }

    /**
     * Get first object by primary key
     *
     * @param Any Primary key
     * @throws \Exception No object found
     * @return Object Self reference
     */
    public function find_or_fail($primary_key)
    {
        $this->where_primary_key($primary_key);
        return $this->first_or_fail();
    }

    /**
     * Insert current object into database and set new primary key
     *
     * @throws \Exception Errow while creating
     * @return Object Self reference
     */
    public function create()
    {
        $this->set_primary_key(null);
        $this->is_valid('create');
        $properties = $this->array();

        $keys = implode(',', array_keys($properties));
        $values = implode(',', array_values(array_map(
            array($this, 'quote'), $properties
        )));

        $this->reset_conditions();
        $result = $this->execute('INSERT INTO '.static::$table.' ('.$keys.') VALUES ('.$values.')');

        if (!$result) {
            throw new \Exception(sprintf('Error while creating %s', $this));
        }

        $this->set_primary_key($this->database->lastInsertRowID());

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
        $this->is_valid('update');
        $properties = $this->array();

        foreach ($properties as $key => $value) {
            $values[] = $key.' = '.$this->quote($value);
        }

        $this->where_primary_key();

        $result = $this->execute('UPDATE '.static::$table.' SET '.implode(',', $values));

        if (!$result) {
            throw new \Exception(sprintf('Error while updating %s', $this));
        }

        return $this;
    }

    /**
     * Delete object in database with current primary key
     *
     * @throws \Exception Error while deleting
     */
    public function delete()
    {
        $this->is_valid('delete');
        $this->where_primary_key();

        $result = $this->execute('DELETE FROM '.static::$table);

        if (!$result) {
            throw new \Exception(sprintf('Error while deleting %s', $this));
        }
        return $this;
    }

    /**
     * Check if all properties are valid, should be overwritten.
     *
     * @param String Origin method
     * @return Boolean
     */
    public function is_valid($origin = null)
    {
        if ($origin != 'create' && empty($this->get_primary_key())) {
            throw new \Exception(sprintf('No primary key set for %s', $this));
        }
    }

    /**
     * Mass assign properties which are not guarded of object with an array
     *
     * @param Array $data Data to be filled
     * @throws \Exception When property is not mass assignable
     * @return Object Self reference
     */
    public function fill($data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->guarded)) {
                throw new \Exception(sprintf('%s of %s is not mass assignable', $key, $this));
            }
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
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
        $value = $value ?? $this->get_primary_key();
        $this->set_primary_key($value);
        return $this
            ->reset_conditions()
            ->where(static::$primary_key.' = ', $value);
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

    /**
     * Set data of current object with array. Use fill instead for public usage.
     *
     * @param Array $data Data to be filled
     * @return Object Self reference
     */
    private function set($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Guarded is supposed to be an array of all properties that are not allowed to be mass assigned via the fill method.
     * If the array not present it will notify the developer with an exception.
     *
     * @throws \Exception
     * @return Array
     */
    private function init_mass_assignment()
    {
        if (empty($this->guarded)) {
            throw new \Exception(sprintf('%s has no property guarded', $this));
        }
        array_push($this->guarded, static::$primary_key);
    }

    /**
     * GETTER
     */

    public function last_query()
    {
        return $this->last_query;
    }

    public function set_primary_key($key)
    {
        $this->{static::$primary_key} = $key;
    }

    public function get_primary_key()
    {
        return $this->{static::$primary_key};
    }

    public function __toString()
    {
        return get_class($this).'('.$this->get_primary_key().')';
    }

}
