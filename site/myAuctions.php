<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Item;
use src\classes\Models\Seller;
use src\classes\Page;
use src\classes\Models\User;
use src\classes\ImageHelper;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class MyAuctions extends Page {

    public function __construct() {
        parent::__construct("template.html");
        $this->user = new User($this->databaseHelper, $this->loggedInUser->getUsername());
        $this->seller = new Seller($this->databaseHelper, $this->user);
        if($this->loggedInUser === null || $this->seller->getUser() === null || $this->seller->getActivationCode() !== null) {
            $this->redirectToIndex();
        }


    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-user-auctions.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        $this->generateLoginAndRegisterTemplates();
        $this->createAuctions();
        return $this->HTMLBuilder->getHTML();
    }

    public function createAuctions(){
        $sellerItems = $this->user->getItems()->getItemsAsSeller();
        $auctionTemplates = array();
        foreach($sellerItems as $item){
            $auctionTemplates[] = $this->generateAuctions($item);
        }
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("customer-auctions", $this->HTMLBuilder->joinHTMLParameters($auctionTemplates));
    }

    private function generateAuctions($item){
        $imageHelper = new ImageHelper();
        $auctionTemplate = new HTMLParameter($this->HTMLBuilder, "content\\auction-template.html");
        $now = new \DateTime();
        $interval = $item->getAuctionEndDateTime()->diff($now);
        $numberOfBids = count($item->getBids());
        $highestPrice = $item->getStartPrice();
        if(count($item->getBids()) >= 1) {
            $highestPrice = $item->getBids()[0]->getAmount();
        }

        $images = $item->getImages();
        foreach($images as $image){
            $imagePath = $imageHelper->getImageLocation($image);
            if (strpos($imagePath,'thumbnails') !== false) {
                $auctionTemplate->addTemplateParameterByString("thumbnail-source", $imagePath);
                break;
            }
            $imagePath = $imageHelper->getImageLocation($image);
            $auctionTemplate->addTemplateParameterByString("image-source", $imagePath);
        }

        $auctionTemplate->addTemplateParameterByString("product-name", $item->getTitle());
        $auctionTemplate->addTemplateParameterByString("product-id", $item->getId());
        $auctionTemplate->addTemplateParameterByString("number-of-bids", $numberOfBids);
        $auctionTemplate->addTemplateParameterByString("highest-bid", number_format($highestPrice, 2, '.',''));

        $auctionTemplate->addTemplateParameterByString("start-date", $item->getAuctionStartDateTime()->format('Y-m-d H:i'));
        $auctionTemplate->addTemplateParameterByString("end-date", $item->getAuctionEndDateTime()->format('Y-m-d H:i'));
        $auctionTemplate->addTemplateParameterByString("time-left",$interval->days." dagen ".$interval->h." uur en ".$interval->i."minuten");

        return $auctionTemplate;
    }

    public function __destruct() {
        parent::__destruct();
    }
}

$page = new MyAuctions();
echo $page->createHTML();