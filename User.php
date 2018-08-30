<?php

class User extends dbModel {

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

}
