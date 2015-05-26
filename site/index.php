<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\models\Rubric;
use src\classes\PageController;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$databaseHelper = new DatabaseHelper();
$rubric = new Rubric($databaseHelper, 1);
$rubric->getChildren();
var_dump($rubric);

//    $arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
//    $PageController = new PageController($arguments);

//$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "home";
//$PageController = new PageController($arguments);

