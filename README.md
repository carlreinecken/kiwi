# Kiwi

A simple abstract SQLite3 database model. Any model can extend from Kiwi to represent a database entity.

## Setup

The model should extend from Kiwi and should have all database table properties as public class variables.

The table name and the primary key has to be defined with a static class variable.

```php
<?php

class User extends Kiwi {

    public $name;
    public $age;

    protected static $table = 'users';
    protected static $primary_key = 'id';

    ...
}
```

Pass the SQLite3 object on each new model instance:

```php
<?php

$user = new User($db);
```

## Read

#### Find

Find a user by primary key:

```php
<?php

$user->find(44);
```

If you need an exception when no user is found, call `find_or_fail()` instead.

#### First

Get first user:

```php
<?php

$user->first();
```

This example returns literally the first object of the database table, so it makes only sense to use `first()` in combination with where conditions.

If you need an exception when no user is found, call `first_or_fail()` instead.

#### All

Return an array of all users:

```php
<?php

$users = $user->all();
```

When no users are found it will return an empty array.

The model instance on which `all()` was called will not be changed.

#### Where Conditions

Call as many where conditions as you like before calling one of the executing methods:

```php
<?php

$users = $user->where('age >', 15)
    ->where('AND age <', 54)
    ->all();

$young_dude = (new User($db))
    ->where('age =', 15)
    ->first_or_fail();
```

Every execution will reset all where conditions. You can only use custom where conditions when using the methods `first()`, `first_or_fail()` or `all()`. After an entity was found it should have a primary key, which is sufficient to identify it for other operations.

## Write

#### Mass assignment

In order to set data to your entity, you can assign the values directly or mass assign the values with `fill()`.

```php
<?php

$user = new User($db);

$user->name = 'Peter';
$user->age = 28;

echo $user->name; // Peter
echo $user->age; // 28

$user->fill([
    'name' => 'Gustav'
]);

echo $user->name; // Gustav
echo $user->age; // 28
```

To prevent mass assignment vulnerabilities you need to define a protected `$guarded` property in your model, which you should fill with property names that are not supposed to be mass assigned. The primary key is by default not mass assignable.

```php
<?php

class User extends Kiwi {
    ...

    protected $guarded;

    __construct($db)
    {
        array_push($this->guarded, 'is_admin', 'is_allowed_to_fly');
        parent::__construct($db);
    }
    ...
}
```

#### Create

```php
<?php

$data = [
    'name' => 'Gustav',
    'age' => 21
];
$new_user = (new User($db))
    ->fill($data)
    ->create();
```

After creating a new entity the new primary key is automatically saved to the model.

#### Update

```php
<?php

$user_forty_four = (new User())
    ->find(44)
    ->fill([
        'age' => 29
    ])
    ->update();
```

#### Delete

```php
<?php

$user->delete();
```

The model will keep the properties, after the model has been deleted from the database.

#### Check Validity

The methods `create`, `update` and `delete` will call a static function `validate` if it exists. This callback should be defined in the model and should expect the arguments `$this` and `$operation`. It is expected that the callback returns an array of strings of validation errors.

```php
<?php // Class User

protected static function validate(User $user, $operation)
{
    if ($operation == self::OPERATION_CREATE || $operation == self::OPERATION_UPDATE) {
        if (empty($user->username)) {
            $errors[] = 'A username is required';
        }
    }
    return $errors ?? [];
}
```

The argument `$operation` gives you the possibility to differentiate between the origin of the call. Every method has its own constant value `OPERATION_CREATE`, `OPERATION_UPDATE` or `OPERATION_DELETE`.

## Relationships

Relationships can be added in your model as simple methods.

#### One to one

The current model has an id of another entity.

```php
<?php
... // User Class

public function creator()
{
    return (new User($this->db))
        ->find($this->created_by);
}
```

#### One to many

```php
<?php
... // User Class

public function orders()
{
    return (new Order($this->db))
        ->where('user_id = ', $this->id)
        ->all();
}
```

Because `user_id` is a table column from orders you should capsulate this where statement in the Order model. Then it could look like `->where_user_is($this->id)->all()`.

## Utilities

#### Array

Get the model as array:

```php
<?php

$user->array();
```

#### Last SQL Statement

After execution, the last SQL query is available with:

```php
<?php

$user->last_query();
```

## Extra: KiwiMeta

KiwiMeta extends from Kiwi and adds some convenient public properties to your model (that also need to be present in the database table):

* updated_at
* updated_by
* created_at
* created_by

You can use them with the methods `create_as()` or `update_as()`:

```php
<?php

$gustav = (new User($db))
    ->find(44)
    ->fill([
        'age' => $user->age * 2
    ])
    // sets also the updated_at property to current timestamp
    // and calls update()
    ->update_as($current_user_id);
```

```php
<?php

$gustav = (new User($db))
    ->fill([
        'name' => 'Gustav',
        'age' => 44
    ])
    // sets also the created_at and updated_* properties
    // and calls create()
    ->create_as($current_user_id);
```
