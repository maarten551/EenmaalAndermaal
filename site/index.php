<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\models\Question;
use src\classes\Models\User;
use src\classes\PageController;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	include $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$databaseHelper = new DatabaseHelper();
//$question = new Question($databaseHelper, 12);
//$question->setQuestionText("Als dit een overval is, hoeveel vrienden zou je dan erbij roepen? Bewijs");
//echo $question->getQuestionText();
//$question->save();
$user = new User($databaseHelper);
echo $user->getQuestion()->getQuestionText();
/*$question->getQuestionText();
$question->save();
$question->setQuestionText($question->getQuestionText().$question->getId());
$question->save();*/

/*$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
$PageController = new PageController($arguments);*/
