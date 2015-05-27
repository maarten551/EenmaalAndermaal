<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\Models\User;
use src\classes\UserHelper;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$databaseHelper = new DatabaseHelper();
$userHelper = new UserHelper();
$user = new User($databaseHelper, '18ribs');
$user->getQuestion()->getQuestionText();
var_dump($userHelper->hasSamePasswordAsHash($user, "Pzch8KjgmMLXjsBr"));
$user->save();
//$rubric = new Rubric($databaseHelper, 1);
//$children = $rubric->getChildren();
//foreach ($children as $child) {
//    $child->getChildren();
//}

var_dump($user);
//    $arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
//    $PageController = new PageController($arguments);

//$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "home";
//$PageController = new PageController($arguments);

$databaseHelper->closeConnection();