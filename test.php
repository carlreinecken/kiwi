<?php

require 'dbModel.php';
require 'dbModelMeta.php';
require 'User.php';

$db = new SQLite3('db.sqlite');

// echo (new User($db))
//     ->fill([
//         'firstname' => 'Colja',
//         'lastname' => 'Peters',
//         'friend_id' => 6
//     ])
//     ->create();
// echo '<p></p>';

// print_r((new User($db))
//     ->where('friend_id = ', 2)
//     ->where('and lastname = ', 'Gump')
//     ->all());
// echo '<p></p>';
// foreach ((new User($db))->all() as $value) {
//     echo $value;
// }
// echo '<p></p>';
//
// echo (new User($db))
//     ->find(7, true)
//     ->destroy();

// echo (new User($db))
//     ->find(4)
//     ->fill([
//         'firstname' => 'Lars'
//     ])
//     ->fill([
//         'updated_by' => 100
//     ])
//     ->update();
// echo '<p></p>';

var_dump((new User($db))->find(1, true));
// var_dump((new User($db))->find(1, true)->user_created()[1]->array());
echo '<p></p>';

// echo (new User($db))
//     ->where('friend_id = ', 3)
//     ->get();
// echo '<p></p>';
