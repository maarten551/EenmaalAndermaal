<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\Models\User;
use src\classes\UserHelper;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

$databaseHelper = new DatabaseHelper();
$userHelper = new UserHelper($databaseHelper);
//$user = $userHelper->loginUser("18ribs", "Pzch8KjgmMLXjsBr", $databaseHelper);
$user = $userHelper->getLoggedInUser();
echo $_SESSION['loggedInUsername'];
if($user !== null) {
	$user->getBirthdate();
	var_dump($user);
} else {
	echo "Login failed";
}

//    $arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
//    $PageController = new PageController($arguments);

//$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "home";
//$PageController = new PageController($arguments);

$databaseHelper->closeConnection();