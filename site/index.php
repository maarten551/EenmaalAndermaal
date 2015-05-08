<?php
use src\classes\HTMLBuilder;
use src\classes\PageController;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	include $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
$PageController = new PageController($arguments);
