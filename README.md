# Kiwi

A simple abstract database model. Any model can extend from Kiwi to represent a database entity.

## Setup

The model should extend from Kiwi and should have all database table properties as public class variables.

The table name and the primary key has to be defined with a static class variable:

```php
class User extends Kiwi {

    public $name;
    public $age;

    static $table = 'users';
    static $primary_key = 'id';

    ...
}
```

Pass your database object on each new instance:

```php
$user = new User($db);
```

## Basics

#### Find

Find one user by the primary key:

```php
$id = 44;
$user->find($id);
```

If you need an exception when no user is found, pass `true` as second argument to `find()`:

```php
try {
    $user->find($id, true);
} catch (\Exception $e) {
    echo $e;
}
```

#### All

Return an array of all Users:

```php
$user->all();
```

If you need an exception when no User are found, pass `true` as argument to `all()`.

#### Create

```php
$data = [
    'name' = 'Gustav',
    'age' = 21
];
$new_user = (new User($db))
    ->fill($data)
    ->create();
```

After creating `$new_user` the new id is automatically saved to the model.

#### Update

```php
$data = [
    'age' = $user->age + 1
];
$user->fill($data)
    ->update();
```

#### Destroy

```php
$user->destroy();
```

#### Where Conditions

Call as many where conditions as you like before calling one of the executing functions:

```php
$user->where('age >', 15)
    ->where('AND age <', 54)
    ->all();
```

**Caution:** Every execution will  reset the where conditions. After an entity was found it should have a primary key, which should be enough to identify it from that moment on. The functions `find()`, `create()` `update()` and `delete()` will ignore all custom where conditions, because they can't use one or only work with a primary key.  Use `get()` instead of `find()` when you want to get the first entity for your where condition.

## Relationships

Relationships can be added in your model as simple functions.

#### One to one

The current model has an id of another entity.

```php
public function creator()
{
    return (new User($this->db))
        ->find($this->created_by);
}
```

#### One to many

Inside an Users class:

```php
public function orders()
{
    return (new Orders($this->db))
        ->where('user_id = ', $this->id)
        ->all();
}
```

#### Many to one

Inside an Orders class:

```php
public function customer()
{
    return (new User($this->db))
        ->find($this->user_id);
}
```

#### Many to many

Inside an Users class:

```php
public function customer()
{
    return (new User($this->db))
        ->find($this->user_id);
}
```

## Utilities

#### Array

Get the model as array:

```php
print_r($user->array());
```

#### Last SQL Statement

After executing the last sql statement is saved inside the `$last_query` property.

## Extra: KiwiMeta

KiwiMeta extends from Kiwi and adds some convenient public properties to your model (that also need to be present in the database table):

* updated_at
* updated_by
* created_at
* created_by

You can use them with the functions `create_as(id)` or `update_as(id)`:

```php
(new User($db))->find($id)
    ->fill([
        'age' => $user->age * 2
    ])
    // sets also the updated_at property to current timestamp
    // and calls update()
    ->update_as($current_user_id);
```

```php
(new User($db))->fill([
        'name' = 'Gustav',
        'age' => 44
    ])
    // sets also the created_at and updated_* properties
    // and calls create()
    ->create_as($current_user_id);
```
