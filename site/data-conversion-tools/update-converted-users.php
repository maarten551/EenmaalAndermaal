<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\Models\Question;
use src\classes\Models\User;
use src\classes\PageController;
use src\classes\UserHelper;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require "..\\".$class_name . '.php';
}

/**
 * @var User[]
 */
$users = array();
/**
 * @var Question[]
 */
$questions = array();
$userHelper = new UserHelper();
$databaseHelper = new DatabaseHelper();

/* Get all the questions */
    $query = "SELECT * FROM [question]";
    $statement = sqlsrv_prepare($databaseHelper->getDatabaseConnection(), $query);
    sqlsrv_execute($statement);
    while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
        $question = new Question($databaseHelper);
        $question->mergeQueryData($row);
        $questions[] = $question;
    }

/* Get all the users */
    $query = "SELECT * FROM [user] WHERE questionAnswer = 'Not generated yet'";
    $statement = sqlsrv_prepare($databaseHelper->getDatabaseConnection(), $query);
    sqlsrv_execute($statement);
    while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
        $user = new User($databaseHelper);
        $user->mergeQueryData($row);
        $user->setPassword($userHelper->generateRandomPassword());
        $user->setQuestionAnswer($userHelper->generateRandomPassword());
        $user->setQuestion($questions[array_rand($questions)]);
        //$user->save();
        $users[] = $user;
    }

/*$user = new User($databaseHelper, '18ribs');
$user->getQuestion()->getQuestionText();*/

var_dump($users);
$databaseHelper->closeConnection();


