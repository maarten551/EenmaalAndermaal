<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Item;
use src\classes\Models\User;
use src\classes\Page;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class MyBids extends Page {

    public function __construct() {
        parent::__construct("template.html");
        if($this->loggedInUser === null) {
            $this->redirectToIndex();
        }
        $this->user = new User($this->databaseHelper, $this->loggedInUser->getUsername());
    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-user-bids.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        $this->createBids();
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }

    public function createBids(){
        $bids = $this->user->getBids();
        $bidTemplates = array();
        foreach($bids as $bid){
            $bidTemplates[] = $this->generateBids($bid);
        }
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("customer-bids", $this->HTMLBuilder->joinHTMLParameters($bidTemplates));
    }

    private function generateBids($bid){
        $bidTemplate = new HTMLParameter($this->HTMLBuilder, "content\\bid-template.html");
        $item = new Item($this->databaseHelper, $bid->getItemId());
        $highestPrice = $item->getBids()[0]->getAmount();

        $bidTemplate->addTemplateParameterByString("title", $item->getTitle());
        $bidTemplate->addTemplateParameterByString("item-id", $item->getId());
        $bidTemplate->addTemplateParameterByString("bid-price", number_format($bid->getAmount(), 2, '.',''));
        $bidTemplate->addTemplateParameterByString("placement-date", $bid->getPlacementDateTime()->format('Y-m-d H:i:s'));

        if ($bid->getAmount() == $highestPrice){
            $bidTemplate->addTemplateParameterByString("is-highest-price", '<text style="color: green">Ja</text>');
        } else {
            $bidTemplate->addTemplateParameterByString("is-highest-price", '<text style="color: red">Nee</text>');
        }

        return$bidTemplate;
    }

    public function __destruct() {
        parent::__destruct();
    }
}

$page = new MyBids();
echo $page->createHTML();