<?php

require 'dbModel.php';
require 'dbModelMeta.php';
require 'User.php';

// echo (new User())
//     ->fill([
//         'firstname' => 'Colja',
//         'lastname' => 'Peters',
//         'friend_id' => 6
//     ])
//     ->create();
// echo '<p></p>';

// print_r((new User())
//     ->where('friend_id = ', 2)
//     ->where('and lastname = ', 'Gump')
//     ->all());
// echo '<p></p>';
// foreach ((new User())->all() as $value) {
//     echo $value;
// }
// echo '<p></p>';
//
// echo (new User())
//     ->find(7, true)
//     ->destroy();

// echo (new User())
//     ->find(4)
//     ->fill([
//         'firstname' => 'Lars'
//     ])
//     ->fill([
//         'updated_by' => 100
//     ])
//     ->update();
// echo '<p></p>';

var_dump((new User())->find(1, true)->user_created()[1]->array());
echo '<p></p>';

// echo (new User())
//     ->where('friend_id = ', 3)
//     ->get();
// echo '<p></p>';
