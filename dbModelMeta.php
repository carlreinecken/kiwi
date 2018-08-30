<?php

class dbModelMeta extends dbModel {

    public $updated_at = 0;
    public $updated_by = 0;
    public $created_at = 0;
    public $created_by = 0;

    public function creating_as($key)
    {
        $this->created_at = time();
        $this->created_by = $key;
        $this->updated_at = $this->created_at;
        $this->updated_by = $this->created_by;

        $this->create();
    }

    public function updating_as($key, $throw = false)
    {
        $this->updated_at = time();
        $this->updated_by = $key;

        $this->update($throw);
    }

}