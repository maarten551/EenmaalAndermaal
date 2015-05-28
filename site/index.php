<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\Models\User;
use src\classes\PageController;
use src\classes\UserHelper;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");
//
//$databaseHelper = new DatabaseHelper();
//$userHelper = new UserHelper($databaseHelper);
////$user = $userHelper->loginUser("18ribs", "Pzch8KjgmMLXjsBr", $databaseHelper);
//$user = $userHelper->getLoggedInUser();
//echo $_SESSION['loggedInUsername'];
//if($user !== null) {
//	$user->getBirthdate();
//	var_dump($user);
//} else {
//	echo "Login failed";
//}

//    $arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "index";
//    $PageController = new PageController($arguments);

//$databaseHelper = new DatabaseHelper();
//$rubric = new \src\classes\Models\Rubric($databaseHelper, 1);
//$children = $rubric->getChildren();
//$parentId = array();
//$children2 = array();
//$childrenId = array();
//
//foreach($children as $child){
//    $parentId[] = $child->getId();
//    $children2[] = $child->getName();
//    $childrenId[] = $child->getId();
//}
//
//for($i = 2; $i<sizeof($childrenId); $i++){
//    $temprubric = new \src\classes\Models\Rubric($databaseHelper, $i);
//    $tempchildren = $temprubric->getChildren();
//    foreach($tempchildren as $temp) {
//        $a = $temp->getName();
//        echo $a . ". parent id =";
//        echo $parentId[$i] . "</br>";
//    }
//}
//
$arguments = (isset($_GET['arg'])) ? $_GET['arg'] : "home";
$PageController = new PageController($arguments);

$databaseHelper->closeConnection();