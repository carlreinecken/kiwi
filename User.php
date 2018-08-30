<?php

class User extends dbModelMeta {

    public $username;
    public $firstname;
    public $lastname;

    public $friend_id;

    static $database = 'db.sqlite';
    static $table = 'users';
    // static $relations = ['friend' => 'friends'];

    public function __construct()
    {
        parent::__construct();
    }

    public function friend()
    {
        return (new $this)->find($this->friend_id);
    }

    public function creator()
    {
        return (new $this)->find($this->created_by);
    }

    public function user_created()
    {
        return (new $this)
            ->where('created_by = ', $this->id)
            ->all();
    }

    public function updater()
    {
        return (new $this)->find($this->updated_by);
    }

}
