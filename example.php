<?php

require 'Kiwi.php';
require 'KiwiMeta.php';
require 'User.php';

$db = new SQLite3('db.sqlite');

function print_row($row) {
    ?>
    <tr>
        <?php foreach ($row as $value): ?>
            <td><?=$value?></td>
        <?php endforeach; ?>
    </tr>
    <?php
}

function print_table($array) {
    ?>
    <table border="1">
        <tr>
            <?php foreach ($array[0] as $key => $v): ?>
                <th><?=$key?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($array as $row): ?>
            <?=print_row($row)?>
        <?php endforeach; ?>
    </table>
    <?php
}

echo '<h1>Kiwi: A simple abstract SQLite3 database model</h1>';

// -----------------------------------------------------------------------------
?>
<h3>My user</h3>
<pre>
    $me = (new User($db))->find(1);
</pre>
<?php
$me = (new User($db))->find(1);
print_table([$me->array()]);
?><pre><?=$me->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>All users</h3>
<pre>
    $me->all();
</pre>
<?php
print_table($me->all());
?><pre><?=$me->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Filtered all users by friend_id and firstname</h3>
<pre>
    $me->where('friend_id = 2')
        ->where('AND firstname LIKE ', '%ar%')
        ->all();
</pre>
<?php
$me->where('friend_id = 2')
    ->where('AND firstname LIKE ', '%ar%');
print_table($me->all());
?><pre><?=$me->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Added Gustav</h3>
<pre>
    $new_user = (new User($db))
        ->fill([
            'firstname' => 'Gustav',
            'lastname' => 'Peters',
            'friend_id' => 2,
            'username' => 'GP'
        ])
        ->create_as($me->id);
</pre>
<pre>
    (new User($db))->all('ORDER BY lastname ASC');
</pre>
<?php
$new_user = (new User($db))
    ->fill([
        'firstname' => 'Gustav',
        'lastname' => 'Peters',
        'friend_id' => 2,
        'username' => 'GP'
    ])
    ->create_as($me->id);
$new_user_id = $new_user->id;
print_table((new User($db))->all('ORDER BY lastname ASC'));
?><pre><?=$new_user->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Updating lastname and username of Gustav</h3>
<pre>
    sleep(1); // otherwise the updated_at would have the same timestamp as before
    $new_user
        ->fill([
            'lastname' => 'Kaufmann',
            'username' => 'GK'
        ])
        ->update_as(44);
</pre>
<?php
sleep(1);
$new_user
    ->fill([
        'lastname' => 'Kaufmann',
        'username' => 'GK'
    ])
    ->update_as(44);
print_table([$new_user]);
?><pre><?=$new_user->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Updating only username of Gustav</h3>
<pre>
    $new_user
        ->fill([
            'username' => 'GKX'
        ])
        ->update_as(64);
</pre>
<?php
$new_user
    ->fill([
        'username' => 'GKX'
    ])
    ->update_as(64);
print_table([$new_user]);
?><pre><?=$new_user->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Updating without required username and primary key throws error</h3>
<pre>
    $new_user->set_primary_key(null)
    $new_user
        ->fill([
            'lastname' => 'Raufmann',
            'username' => null,
            // 'is_admin' => true // guarded property should throw error
        ])
        ->update_as(45);
</pre>
<?php
$new_user->set_primary_key(null);
try {
    $new_user->fill([
        'lastname' => 'Raufmann',
        'username' => null,
        // 'is_admin' => true // guarded property should throw error
    ])
    ->update_as(45);
    echo '<p>No error?</p>';
} catch (\Exception $e) {
    $exploded = explode("\n", $e->getMessage());
    echo "<p>Validation Errors: <br />".implode('<br /> ', $exploded)."</p>";
}

// -----------------------------------------------------------------------------
?>
<h3>Friend of Gustav</h3>
<pre>
    $friend_of_gustav = $new_user->friend();
</pre>
<?php
$friend_of_gustav = $new_user->friend();
print_table([$friend_of_gustav]);
?><pre><?=$friend_of_gustav->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Friend of friend of Gustav should throw error</h3>
<pre>
    $friend_of_friend_of_gustav = $friend_of_gustav->friend();
</pre>
<?php
try {
    $friend_of_friend_of_gustav = $friend_of_gustav->friend();
    echo '<p>No error?</p>';
} catch (\Exception $e) {
    echo '<p>Error: '.$e->getMessage().'</p>';
}

// -----------------------------------------------------------------------------
?>
<h3>Delete Gustav</h3>
<pre>
    $gustav = (new User($db))
        ->find($new_user->id)
        ->delete();
</pre>
<pre>
    (new User($db))->all();
</pre>
<?php
$gustav = (new User($db))
    ->find($new_user_id)
    ->delete();
print_table((new User($db))->all());
?><pre><?=$gustav->last_query()?></pre><?php
