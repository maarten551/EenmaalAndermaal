<?php
namespace src\classes;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Rubric;
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
        $this->databaseHelper = new DatabaseHelper();
        $this->userHelper = new UserHelper($this->databaseHelper);
        $this->user = $this->userHelper->getLoggedInUser();

        $this->HTMLBuilder = new HTMLBuilder($templateFileName);
    }

    protected function __destruct() {
        $this->databaseHelper->closeConnection();
    }
}