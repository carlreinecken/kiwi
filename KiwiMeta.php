<?php

/**
 * Kiwi: A simple abstract SQLite3 database model.
 *
 * @version v1.1.2
 * @copyright Copyright (c) 2018, Carl Reinecken <carl@reinecken.net>
 */

class KiwiMeta extends Kiwi {

    public $updated_at = 0;
    public $updated_by = 0;
    public $created_at = 0;
    public $created_by = 0;

    public function __construct($db)
    {
        parent::__construct($db);
        array_push($this->guarded, 'updated_at', 'updated_by', 'created_at', 'created_by');
    }

    public function create_as($key)
    {
        $this->created_at = time();
        $this->created_by = $key;
        $this->updated_at = $this->created_at;
        $this->updated_by = $this->created_by;

        return $this->create();
    }

    public function update_as($key)
    {
        if ($this->changed()) {
            $this->updated_at = time();
            $this->updated_by = $key;
        }

        return $this->update();
    }

}
