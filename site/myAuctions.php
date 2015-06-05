<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Item;
use src\classes\Models\Seller;
use src\classes\Page;
use src\classes\Models\User;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class MyAuctions extends Page {

    public function __construct() {
        parent::__construct("template.html");
        $this->seller = new Seller($this->databaseHelper, $this->loggedInUser);
        if($this->loggedInUser === null || $this->seller->getUser() === null || $this->seller->getActivationCode() !== null) {
            $this->redirectToIndex();
        }

    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-user-auctions.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);


        $this->createAuctions();
        return $this->HTMLBuilder->getHTML();
    }

    public function createAuctions(){

    }

    private function generateAuctions(){

    }

    public function __destruct() {
        parent::__destruct();
    }
}

$page = new MyAuctions();
echo $page->createHTML();