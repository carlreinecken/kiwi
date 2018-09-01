<?php

class User extends KiwiMeta {

    public $id;
    public $username;
    public $firstname;
    public $lastname;

    public $friend_id;

    static $table = 'users';
    static $primary_key = 'id';

    public function friend()
    {
        return (new $this($this->_db))
            ->find($this->friend_id);
    }

    public function creator()
    {
        return (new $this($this->_db))
            ->find($this->created_by);
    }

    public function user_created()
    {
        return (new $this($this->_db))
            ->where('created_by = ', $this->id)
            ->all();
    }

    public function updater()
    {
        return (new $this($this->_db))
            ->find($this->updated_by);
    }

}
