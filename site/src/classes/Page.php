<?php
namespace src\classes;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Question;
use src\classes\Models\User;

abstract class Page {
    /**
     * @var DatabaseHelper
     */
    protected $databaseHelper;
    /**
     * @var UserHelper
     */
    protected $userHelper;
    /**
     * @var User
     */
    protected $loggedInUser;
    /**
     * @var HTMLBuilder
     */
    protected $HTMLBuilder;

    abstract protected function createHTML();

    /**
     * @param $templateFileName
     */
    protected function __construct($templateFileName) {
        $this->HTMLBuilder = new HTMLBuilder($templateFileName);
        $this->databaseHelper = new DatabaseHelper();
        $this->userHelper = new UserHelper($this->databaseHelper, $this->HTMLBuilder);
        $this->handleRequestParameters();
        if($this->loggedInUser === null) {
            $this->loggedInUser = $this->userHelper->getLoggedInUser();
        }
    }

    protected function __destruct() {
        $this->databaseHelper->closeConnection();
    }

    private function handleRequestParameters() {
        if(array_key_exists('login', $_POST)) {
            $this->loggedInUser = $this->userHelper->loginUser($_POST['login-username'], $_POST['login-password']);
        } else if (array_key_exists('logout', $_GET)) {
            $this->userHelper->logoutUser();
        } else if (array_key_exists('register', $_POST)) {
            $this->userHelper->registerUser();
        }
    }

    protected function generateLoginAndRegisterTemplates() {
        if($this->loggedInUser === null) {
            $registerModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\register-modal.html");
            $loginModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\inloggen-modal.html");
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("inloggen-modal", $loginModal);
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("register-modal", $registerModal);
            $this->generateQuestionTemplate();
        } else {
            $loggedOnTemplate = new HTMLParameter($this->HTMLBuilder, "content\\user-is-logged-on.html");
            $loggedOnTemplate->addTemplateParameterByString("user-username", $this->loggedInUser->getUsername());
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("inloggen-modal", $loggedOnTemplate);
        }
    }

    protected function generateQuestionTemplate(){
        $questions = $this->getQuestions();
        $questionTemplates = array();
        foreach($questions as $question){
            $questionTemplate = new HTMLParameter($this->HTMLBuilder, "content\\question.html");
            $questionTemplate->addTemplateParameterByString("question-name", $question->getQuestionText());
            $questionTemplate->addTemplateParameterByString("question-value", $question->getQuestionText());
            $questionTemplates[] = $questionTemplate;
        }

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("questions", $this->HTMLBuilder->joinHTMLParameters($questionTemplates));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("max-birthdate", date('d-m-Y'));
    }

    /**
     * @return Question[]
     */
    private function getQuestions(){
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "select questionText from question");
        if($statement === false) {
            echo "Error in executing statement.\n";
            die( print_r( sqlsrv_errors(), true));
        } else {
            $questions = array();
            while ($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $question = new Question($this->databaseHelper);
                $question->mergeQueryData($row);
                $questions[] = $question;
            }
            return $questions;
        }
    }
}