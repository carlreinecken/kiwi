# dbModel

One-file php class that any model can extend from to represent a database entity.

## Setup

The model should extend from dbModel and should have all database table properties as public class variables.

The table name can be defined by a static class variable `$table`.

The table must have as unique key an `id` field.

```php
class User extends dbModel {

    public $name;
    public $age;

    static $table = 'users';

    ...
}
```

Pass your database object on each new instance:

```php
$user = new User($db);
```

## Basics

#### Find

Find one user by the primary key `id` (which is currently not configurable):

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

**Caution:** Calling `find()` after your own where conditions will fail, because it doesn't connect with an operator. Use `get()` instead.

**Caution:** The where clause that was built, will be reset after calling an executing method. After the entity was found it should have a unique key which should be enough to identify it for future operations.

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

Inside an User class:

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

Inside an User class:

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

## Extra: dbModelMeta

dbModelMeta extends from dbModel and adds some convenient public properties to your model (that also need to be present in the database table):

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
