<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Page;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class UserTemplate extends Page {

    public function __construct() {
        parent::__construct("template.html");
    }

    public function createHTML()
    {

        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-user.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        var_dump($this->loggedInUser);

        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }
}

$user = new UserTemplate();
echo $user->createHTML();