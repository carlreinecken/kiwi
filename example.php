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

echo '<h1>Kiwi: A simple abstract database model</h1>';

// -----------------------------------------------------------------------------
?>
<h3>My user</h3>
<pre>
    $me = (new User($db))->find(1);
</pre>
<?php
$me = (new User($db))->find(1);
print_table([$me->array()], $me->last_query());
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
    $me->where('friend_id = ', 2)
        ->where('AND firstname LIKE ', '%ar%');
</pre>
<?php
$me->where('friend_id = ', 2)
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
<?php
$new_user = (new User($db))
    ->fill([
        'firstname' => 'Gustav',
        'lastname' => 'Peters',
        'friend_id' => 2,
        'username' => 'GP'
    ])
    ->create_as($me->id);
print_table((new User($db))->all());
?><pre><?=$new_user->last_query()?></pre><?php

// -----------------------------------------------------------------------------
?>
<h3>Lastname and username of Gustav updated</h3>
<pre>
    $new_user
        ->fill([
            'lastname' => 'Kaufmann',
            'username' => 'GK'
        ])
        ->update_as($me->id)
        ->find($new_user->id);
</pre>
<?php
$new_user
    ->fill([
        'lastname' => 'Kaufmann',
        'username' => 'GK'
    ])
    ->update_as($me->id)
    ->find($new_user->id);
print_table([$new_user], $new_user->last_query());
?><pre><?=$new_user->last_query()?></pre><?php

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
    echo '<p>'.$e->getMessage().'</p>';
}

// -----------------------------------------------------------------------------
?>
<h3>Delete Gustav</h3>
<pre>
    $gustav = (new User($db))
        ->find($new_user->id)
        ->delete();
</pre>
<?php
$gustav = (new User($db))
    ->find($new_user->id)
    ->delete();
print_table((new User($db))->all(), 'Delete Gustav');
?><pre><?=$gustav->last_query()?></pre><?php
