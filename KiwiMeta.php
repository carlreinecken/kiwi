<?php

class KiwiMeta extends Kiwi {

    public $updated_at = 0;
    public $updated_by = 0;
    public $created_at = 0;
    public $created_by = 0;

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
        $this->updated_at = time();
        $this->updated_by = $key;

        return $this->update();
    }

}
