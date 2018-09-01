<?php

class User extends KiwiMeta {

    public $id;
    public $username;
    public $firstname;
    public $lastname;

    public $friend_id;

    protected static $table = 'users';
    protected static $primary_key = 'id';

    public function friend()
    {
        return (new $this($this->database))
            ->find($this->friend_id);
    }

    public function creator()
    {
        return (new $this($this->database))
            ->find($this->created_by);
    }

    public function created_user()
    {
        return (new $this($this->database))
            ->where('created_by = ', $this->id)
            ->all();
    }

    public function updater()
    {
        return (new $this($this->database))
            ->find($this->updated_by);
    }

}
