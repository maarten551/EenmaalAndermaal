<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Page;
use src\classes\Models\File;
use src\classes\Models\Item;
use \src\classes\ImageHelper;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class Product extends Page {

    public function __construct() {
        parent::__construct("template.html");
    }

    public function createHTML()
    {
        $imageHelper = new ImageHelper();
        $item = new Item($this->databaseHelper, $_GET["product"]);

        $auctionEndDate = $item->getAuctionEndDateTime();
        $now = new \DateTime();
        $interval = $auctionEndDate->diff($now);

        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-productoverzicht.html");
        $question = new HTMLParameter($this->HTMLBuilder, "content\\question.html");
        $registerModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\register-modal.html");
        $loginModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\inloggen-modal.html");
        $thumbnail = new HTMLParameter($this->HTMLBuilder, "content\\product\\product-thumbnail.html");

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("inloggen-modal", $loginModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("register-modal", $registerModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("questions", $question);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("max-birthdate", date('d-m-Y'));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        //getting all information from the product
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("title", $item->getTitle());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("description", $item->getDescription());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("seller", $item->getSeller()->getUsername());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("auction-enddate", $auctionEndDate->format('Y-m-d H:i'));

        if ($item->getIsAuctionClosed()){
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", "Deze veiling is gesloten, u kunt niet meeer bieden");
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "disabled");
        } else {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", "U heeft nog ".$interval->days." dagen en ".$interval->h." uur over om te bieden");
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "enabled");
        }
        //TODO add more information to the product view
        foreach ($item->getImages() as $index => $image) {
            $imagePath = $imageHelper->getImageLocation($image);

            if($index == 0) {
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("img-source", $imagePath);
            }
            if($index >= 1) {
                if (strpos($imagePath,'pics') !== false) {
                    $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("thumbnails", $thumbnail);
                    $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("img-source-thumb", $imagePath);
                }
            }
        }

        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }
}

$page = new Product();
echo $page->createHTML();