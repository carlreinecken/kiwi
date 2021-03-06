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

    protected static function validate(User $user, $operation)
    {
        if ($operation == self::OPERATION_CREATE || $operation == self::OPERATION_UPDATE) {
            if (empty($user->username)) {
                $errors[] = 'A username is required';
            }
        }
        return $errors ?? [];
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
