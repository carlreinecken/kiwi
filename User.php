<?php

class User extends KiwiMeta {

    public $id;
    public $username;
    public $firstname;
    public $lastname;

    public $friend_id;

    protected static $table = 'users';
    protected static $primary_key = 'id';

    protected $guarded = ['is_admin'];

    public function check($origin = null)
    {
        switch ($origin) {
            case self::ORIGIN_METHOD_CREATE:
            case self::ORIGIN_METHOD_UPDATE:
                if (empty($this->username)) {
                    $this->validation_errors[] = 'A username is required';
                }
                break;
        }
        parent::check($origin);
    }

    public function friend()
    {
        return (new $this($this->database))
            ->find_or_fail($this->friend_id);
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
