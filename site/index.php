<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\models\Rubric;
use src\classes\Models\User;
use src\classes\PageController;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$databaseHelper = new DatabaseHelper();
$user = new User($databaseHelper, '18ribs');
$user->getQuestion()->getQuestionText();
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