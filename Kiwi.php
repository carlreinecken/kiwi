<?php

/**
 * Kiwi: A simple abstract SQLite3 database model.
 *
 * @version v1.2.2
 * @copyright Copyright (c) 2018, Carl Reinecken <carl@reinecken.net>
 */

abstract class Kiwi {

    protected $database;

    private $conditions;
    private $last_query;
    private $original = [];

    const SELECT_FROM_ALL = 'SELECT * FROM ';
    const NOT_FOUND_EXCEPTION_CODE = 404;

    const OPERATION_CREATE = 'OPERATION_CREATE';
    const OPERATION_UPDATE = 'OPERATION_UPDATE';
    const OPERATION_DELETE = 'OPERATION_DELETE';

    public function __construct($db)
    {
        $this->enable_mass_assignment();
        $this->database = $db;
    }

    /**
     * Cast object to an array
     *
     * @return Array
     */
    public function array()
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * Count all objects in the database with current query
     *
     * @return Number
     */
    public function count()
    {
        $count = 'count(*)';
        $result = $this->execute('SELECT '.$count.' FROM '.static::$table);
        return $result->fetchArray(SQLITE3_ASSOC)[$count];
    }

    /**
     * Get all objects from the table with current query
     *
     * @param Boolean Throw exception if no object is found
     * @throws \Exception No objects found
     * @return Self reference
     */
    public function all($suffix = '')
    {
        $objects = [];
        $result = $this->execute(self::SELECT_FROM_ALL.static::$table, $suffix);
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
        $results = $this->execute(self::SELECT_FROM_ALL.static::$table)->fetchArray(SQLITE3_ASSOC);

        if (!$results) {
            throw new \Exception(sprintf('No %s found', $this), self::NOT_FOUND_EXCEPTION_CODE);
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
        } catch (\Exception $e) {
            if ($e->getCode() != self::NOT_FOUND_EXCEPTION_CODE) {
                throw $e;
            }
        }

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
        $this->check(self::OPERATION_CREATE);

        $properties = $this->array();
        $keys = implode(', ', array_keys($properties));
        $values = implode(', ', array_values(array_map(
            array($this, 'quote'), $properties
        )));

        $this->conditions = '';
        $result = $this->execute('INSERT INTO '.static::$table.' ('.$keys.') VALUES ('.$values.')');

        if (!$result) {
            throw new \Exception(sprintf('Error while creating %s', $this));
        }

        $this->set_primary_key($this->database->lastInsertRowID());
        $this->refresh_original();

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
        if (empty($this->original)) {
            throw new \Exception(sprintf('Tried to update %s that may not exist', $this));
        }
        if (!$this->changed()) {
            throw new \Exception(sprintf('No values were changed, when tried to update %s', $this));
        }

        $this->check(self::OPERATION_UPDATE);

        $properties = $this->array();
        foreach ($properties as $key => $value) {
            if (isset($this->original[$key]) && $this->original[$key] == $value) {
                continue;
            }
            $values[] = $key.' = '.$this->quote($value);
        }


        $this->where_primary_key();
        $result = $this->execute('UPDATE '.static::$table.' SET '.implode(', ', $values));

        if (!$result) {
            throw new \Exception(sprintf('Error while updating %s', $this));
        }

        $this->refresh_original();
        return $this;
    }

    /**
     * Delete object in database with current primary key
     *
     * @throws \Exception Error while deleting
     */
    public function delete()
    {
        $this->check(self::OPERATION_DELETE);
        $this->where_primary_key();

        $result = $this->execute('DELETE FROM '.static::$table);

        if (!$result) {
            throw new \Exception(sprintf('Error while deleting %s', $this));
        }

        $this->original = [];
        return $this;
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
     * Resets the object to its original values
     */
    public function reset()
    {
        $this->set($this->original);
        return $this;
    }

    /**
     * Append limit and an optional offset to the sql query
     *
     * @param Integer $limit The amount of expected results
     * @param Integer $offset
     * @return Object Self reference
     */
    public function limit($limit, $offset = 0)
    {
        $this->conditions .= ' LIMIT '.$this->quote((int) $limit);
        $this->conditions .= ($offset > 0) ? ' OFFSET '.$this->quote((int) $offset) : '';

        return $this;
    }

    /**
     * Append a where condition to the current query
     *
     * @param String $column_and_operator Logical operator, column and a comparison operator
     * @param Any The value
     * @return Object Self reference
     */
    public function where($column_and_operator, $value = '')
    {
        $this->conditions .= (stripos($this->conditions, 'WHERE') === false) ? ' WHERE ' : ' ';
        $this->conditions .= $column_and_operator.$this->quote($value, true);

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
        $this->conditions = '';
        $value = $value ?? $this->get_primary_key();
        $this->set_primary_key($value);
        return $this->where(static::$primary_key.' = ', $value);
    }

    /**
     * Escape strings and set null values
     *
     * @param Any The unprocessed value
     * @return Any The processed value
     */
    protected function quote($value, $allow_empty_strings = false)
    {
        if ($value === null) {
            return 'NULL';
        } else if ($allow_empty_strings && $value === '') {
            return '';
        } else if (is_string($value)) {
            return '\''.$this->database->escapeString($value).'\'';
        }
        return $value;
    }

    /**
     * Checks if all properties are valid, and writes validation errors to a protected property array.
     * Will be called by the create, update and delete method, which will set an operation argument.
     *
     * @param Object Self reference
     * @param String Possible constants: OPERATION_CREATE, OPERATION_UPDATE and OPERATION_DELETE
     * @throws \Exception When at least one validation error exists
     * @return Boolean
     */
    protected function check($operation)
    {
        $validation_errors = (method_exists($this, 'validate')) ? static::validate($this, $operation) : [];
        if ($operation != self::OPERATION_CREATE && empty($this->get_primary_key())) {
            $validation_errors[] = sprintf('No primary key set for %s', $this);
        }
        if (!empty($validation_errors)) {
            throw new \Exception(implode("\n", $validation_errors));
        }
    }

    /**
     * Executes a sql query with where conditions
     *
     * @param String Beginning of sql query
     * @return Any The result of the database query
     */
    private function execute($prefix, $suffix = '')
    {
        $this->last_query = $prefix.$this->conditions.' '.$suffix;
        $this->conditions = '';
        return $this->database->query($this->last_query);
    }

    /**
     * Mass assign properties to this object with an array. Use fill instead for public usage.
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
        $this->refresh_original();

        return $this;
    }

    /**
     * Guarded is supposed to be an array of all properties that are not allowed to be mass assigned via the fill method.
     * If the array not present it will notify the developer with an exception.
     *
     * @throws \Exception
     * @return Array
     */
    private function enable_mass_assignment()
    {
        if (!isset($this->guarded)) {
            throw new \Exception(sprintf('Property $guarded of %s is undefined', $this));
        }
        array_push($this->guarded, static::$primary_key);
    }

    /**
     * Sets the original values to the values of the object
     */
    private function refresh_original()
    {
        $this->original = array_merge($this->original, $this->array());
    }

    /**
     * SETTER & GETTER
     */

    /**
    * Wether there is a difference between the current object and its original values
    *
    * @return Boolean
    */
    public function changed()
    {
        return empty($this->diff()) === false;
    }

    /**
    * Returns an array of the keys that differ from the original object
    *
    * @return Array
    */
    public function diff()
    {
        return array_keys(array_diff_assoc($this->array(), $this->original));
    }

    /**
     * Last query after calling any of the excuting methods
     *
     * @return String
     */
    public function last_query()
    {
        return $this->last_query;
    }

    public function set_primary_key($key)
    {
        $this->{static::$primary_key} = $key;
        return $this;
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
