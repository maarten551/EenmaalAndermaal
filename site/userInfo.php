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
        $activateSellerCodeModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\activate-sellercode-modal.html");
        $createSellerModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\create-selleraccount-modal.html");
        $changePasswordModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\change-password-modal.html");
        $phoneNumberModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\phonenumber-modal.html");


        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("activate-sellercode", $activateSellerCodeModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("create-seller", $createSellerModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("change-password", $changePasswordModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("manage-phonenumbers", $phoneNumberModal);

        //$this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("username", $this->loggedInUser->getUsername());
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            if ($_POST["disabled"] == "disabled"){
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("disabled", "enabled");
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("change-or-save", "Opslaan");
            } else if ($_POST["disabled"] == "enabled"){
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("disabled", "disabled");
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("change-or-save", "Wijzigen");
            }
        } else {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("disabled", "disabled");
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("change-or-save", "Wijzigen");
        }
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }
}

$user = new UserTemplate();
echo $user->createHTML();