<?php
define('CLI_SCRIPT', true);
require_once '../../../config.php';
require_once '../classes/controller.php';

use block_sharing_cart\controller;

//namespace block_sharing_cart;

//require_once('../classes/controller.php');

$controller = new controller();

echo $controller->backup(2, false, 2);


//function test ($foo, $bar) {
//    $foobar = $foo . $bar;
//
//    return $foobar;
//}
//
//$foobar = test('John', 'Doo');