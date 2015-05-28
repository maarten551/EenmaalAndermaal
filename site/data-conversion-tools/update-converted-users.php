<?php
require_once "../src/libraries/password.php"; //For password hashing functionality for PHP < 5.5

use src\classes\DatabaseHelper;
use src\classes\Models\Question;
use src\classes\Models\User;
use src\classes\UserHelper;

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require "..\\".$class_name . '.php';
}

/**
 * @var Question[]
 */
$questions = array();
$databaseHelper = new DatabaseHelper();
$userHelper = new UserHelper($databaseHelper);

/* Get all the questions */
    $query = "SELECT  * FROM [question]";
    $statement = sqlsrv_prepare($databaseHelper->getDatabaseConnection(), $query);
    sqlsrv_execute($statement);
    while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
        $question = new Question($databaseHelper);
        $question->mergeQueryData($row);
        $questions[] = $question;
    }

/* Get all the users */
    $query = "SELECT TOP 500 * FROM [user] WHERE questionAnswer = 'Not generated yet'"; //If to many rows are gotten from the database, the script will take to long process and the script will be eliminated, just refresh the page to process the next 500 users
    $statement = sqlsrv_prepare($databaseHelper->getDatabaseConnection(), $query);
    sqlsrv_execute($statement);
    $startTime = microtime(true);

    echo "Username; Password; Question answer; Question<br/>";
    while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
        $user = new User($databaseHelper);
        $user->mergeQueryData($row);
        /**
         * @var Question
         */
        $selectedQuestion = $questions[array_rand($questions)];

        $user->setPassword($userHelper->generateRandomPassword());
        $user->setQuestionAnswer($userHelper->generateRandomPassword());
        $user->setQuestion($selectedQuestion);
        echo $user->getUsername()."; ".$user->getPassword()."; ".$user->getQuestionAnswer()."; ".$selectedQuestion->getQuestionText()." <br/>";
        $userHelper->hashPassword($user);
        $user->save();
        unset($user); //Clean up memory
    }

$databaseHelper->closeConnection();