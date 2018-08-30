<?php

abstract class dbModel {

    public $id;

    protected $db;
    protected $query;
    protected $clause;
    protected $last_query;

    public function __construct()
    {
        $this->db = new SQLite3($this->config('database'));

        $this->query = 'SELECT * FROM '.$this->config('table');
    }

    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * Get all objects from the table with current query
     *
     * @param boolean $throw Throw exception if no object is found
     * @throws \Exception No objects found
     * @return object Self reference
     */
    public function all($throw = false)
    {
        $objects = [];
        $result = $this->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
           array_push($objects, (new $this)->fill($row));
        }

        if (empty($objects) && $throw) {
            throw new \Exception(sprintf('No %s found', get_class($this)));
        }
        return $objects;
    }

    /**
     * Get first object with current query
     *
     * @param boolean $throw Throw exception if no object is found
     * @throws \Exception No object found
     * @return object Self reference
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
     * @param int $id Primary key
     * @param boolean $throw Throw exception if no object is found
     * @throws \Exception No object found
     * @return object Self reference
     */
    public function find($id, $throw = false)
    {
        return $this
            ->where('id = ', $id)
            ->get($throw);
    }

    /**
     * Insert current object into database and set new primary key
     *
     * @return object Self reference
     */
    public function create()
    {
        $properties = $this->array();

        $keys = implode(',', array_keys($properties));
        $values = implode(',', array_values(array_map(
            array($this, 'quote'), $properties
        )));

        $this->db->exec('INSERT INTO '.$this->config('table').' ('.$keys.') VALUES ('.$values.')');
        $this->id = $this->db->lastInsertRowID();

        return $this;
    }

    /**
     * Update object in database with current object
     *
     * @return object Self reference
     */
    public function update($throw = false)
    {
        $properties = $this->array();

        foreach ($properties as $key => $value) {
            $values[] = $key.' = '.$this->quote($value);
        }

        $result = $this->execute('UPDATE '.$this->config('table').' SET '.implode(',', $values));

        if (!$result && $throw) {
            throw new \Exception('Error while updating %s', get_class($this));
        }

        return $this;
    }

    public function destroy()
    {
        $result = $this->execute('DELETE FROM '.$this->config('table'));
    }

    /**
     * Set data of current object with array
     *
     * @param array $data Data to be filled
     * @return object Self reference
     */
    public function fill(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && !empty($value)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Add a where condition to the current query
     *
     * @param string $column_and_operator Logical operator, column and a comparison operator
     * @param any $value The value
     * @return object Self reference
     */
    public function where($column_and_operator, $value)
    {
        $this->clause .= (stripos($this->clause, 'WHERE') === false)
            ? ' WHERE '
            : ' ';

        $this->clause .= $column_and_operator.$this->quote($value);

        return $this;
    }

    /**
     * Cast the current object as array
     *
     * @return array public properties of model
     */
    public function array()
    {
        return json_decode(json_encode($this), true);
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
            return '\''.$this->db->escapeString($value).'\'';
        }
        return $value;
    }

    /**
     * Query the current SQL with where conditions
     */
    protected function execute($sql = null)
    {
        $query = ($sql) ? $sql : $this->query;
        $result = $this->db->query($query.$this->clause);
        $this->last_query = $query.$this->clause;
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
        throw new \Exception(sprintf('No %s set for %s model', $name, get_class($this)));

    }

    public function __toString()
    {
        return json_encode($this);
    }

}
