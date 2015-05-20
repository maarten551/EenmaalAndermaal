<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\models\Question;
use src\classes\PageController;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	include $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$databaseHelper = new DatabaseHelper();
$question = new Question($databaseHelper);
$question->getQuestionText();
$question->save();

//$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
//$PageController = new PageController($arguments);
