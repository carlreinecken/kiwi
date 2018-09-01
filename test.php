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

function print_table($array, $title, $query = '') {
    ?>
    <h3><?=$title?></h3>
    <pre>
        <?=$query?>
    </pre>
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

$me = (new User($db))->find(1);
print_table([$me->array()], 'My user', $me->get_last_query());

print_table($me->all(), 'All users', $me->get_last_query());

$me->where('friend_id = ', 2)
    ->where('AND firstname LIKE ', '%ar%');
print_table($me->all(), 'Filtered all users by friend_id and firstname', $me->get_last_query());

$new_user = (new User($db))
    ->fill([
        'firstname' => 'Gustav',
        'lastname' => 'Peters',
        'friend_id' => 44,
        'username' => 'GP'
    ])
    ->create_as($me->id);
print_table((new User($db))->all(), 'Added Gustav with id '.$new_user->id, $new_user->get_last_query());

$new_user
    ->fill([
        'lastname' => 'Kaufmann',
        'username' => 'GK'
    ])
    ->update();
print_table((new User($db))->all(), 'Lastname and username of Gustav updated', $new_user->get_last_query());

$gustav = (new User($db))
    ->find($new_user->id);
print_table([$gustav->array()], 'Find Gustav', $gustav->get_last_query());

$gustav->delete();
print_table((new User($db))->all(), 'Delete Gustav', $gustav->get_last_query());
