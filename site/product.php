<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Models\Bid;
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
    /**
     * @var Item
     */
    private $item = null;

    public function __construct() {
        parent::__construct("template.html");

//        if(!array_key_exists("product", $_GET) || !is_numeric($_GET["product"]) || $this->item->getSeller() === null) {
//            $this->redirectToIndex();
//        }
    }

    public function handleRequestParameters() {
        parent::handleRequestParameters();
        $this->item = new Item($this->databaseHelper, $_GET["product"]);
        $user = $this->userHelper->getLoggedInUser();
        if(array_key_exists("bid-on-product", $_POST)) {
            if($user !== null) {
                $bid = new Bid($this->databaseHelper, $_POST['bid-amount'], $this->item->getId());
                $bid->setUser($user);
                if ($bid->save() === false) {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Bieding niet geplaatst", "Bieding is niet hoog genoeg."));
                } else {
                    $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Bieding geplaatst", "Uw bieding is geplaatst."));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Bieding niet geplaatst", "U bent niet ingelogd."));
            }
        }
    }

    public function createHTML()
    {
        $imageHelper = new ImageHelper();
        $interval = $this->item->getAuctionEndDateTime()->diff(new DateTime());

        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-productoverzicht.html");
        $thumbnail = new HTMLParameter($this->HTMLBuilder, "product\\product-thumbnail.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        //getting all information from the product
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("title", $this->item->getTitle());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("description", $this->item->getDescription());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("seller", $this->item->getSellerId());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("auction-enddate", $this->item->getAuctionStartDateTime()->format('Y-m-d H:i'));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("payment-instruction", $this->item->getPaymentInstruction());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("shipping-instruction", $this->item->getShippingInstruction());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("shipping-cost", $this->item->getShippingCost());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("bid-container", $this->generateBidTemplates());


        if ($this->item->getIsAuctionClosed()){
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", "Deze veiling is gesloten, u kunt niet meer bieden");
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "disabled");
        } else {
            if ($interval->days > 1){
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", "U heeft nog ".$interval->days." dagen ".$interval->h." uur en ".$interval->i." minuten over om te bieden");
            } else {
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", "U heeft nog ".$interval->days." dag ".$interval->h." uur en ".$interval->i." minuten over om te bieden");
            }
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "enabled");
        }

        if(!$this->loggedInUser){
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "disabled");
        }

        //TODO add more information to the product view
        foreach ($this->item->getImages() as $index => $image) {
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

        $this->processHighestBid();
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
        
    }

    public function __destruct() {
        parent::__destruct();
    }

    /**
     * @return HTMLParameter
     */
    private function generateBidTemplates() {
        $bidContainerTemplate = new HTMLParameter($this->HTMLBuilder, "product\\bid\\bid-container.html");

        /**
         * @var $bidTemplates HTMLParameter[]
         */
        $bidTemplates = array();
        $bids = $this->item->getBids();
        foreach ($bids as $bid) {
            $bidTemplate = new HTMLParameter($this->HTMLBuilder, "product\\bid\\bid-item.html");
            $bidTemplate->addTemplateParameterByString("username", $bid->getUsername());
            $bidTemplate->addTemplateParameterByString("amount", $bid->getAmount());
            $bidTemplate->addTemplateParameterByString("timeOfPlacement", $bid->getPlacementDateTime()->format("d-m-Y H:m:s"));
            $bidTemplates[] = $bidTemplate;
        }

        $bidContainerTemplate->addTemplateParameterByString("bids", $this->HTMLBuilder->joinHTMLParameters($bidTemplates));
        return $bidContainerTemplate;
    }

    private function processHighestBid() {
        $highestPrice = number_format($this->item->getStartPrice(), 2, '.', '');
        if(count($this->item->getBids()) >= 1) {
            $highestPrice = number_format($this->item->getBids()[0]->getAmount(), 2, '.', '');
        }

        $minimalIncrement = number_format($this->calculateMinimumBidIncrement($highestPrice), 2, '.', '');
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("highest-bid", $highestPrice);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("minimal-increment", $minimalIncrement);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("minimal-new-value", number_format($highestPrice + $minimalIncrement, 2, '.', ''));
    }

    /**
     * @param $highestPrice float
     * @return float
     */
    private function calculateMinimumBidIncrement($highestPrice)
    {
        if ($highestPrice < 50) {
            return 0.5;
        } else if ($highestPrice < 500) {
            return 1;
        } else if ($highestPrice < 1000) {
            return 5;
        } else if ($highestPrice < 5000) {
            return 10;
        } else if ($highestPrice >= 5000) {
            return 50;
        }
    }
}

$page = new Product();
echo $page->createHTML();