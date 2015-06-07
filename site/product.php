<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Models\Bid;
use src\classes\Models\Feedback;
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

    /**
     *
     */
    public function __construct() {
        parent::__construct("template.html");

        if(!array_key_exists("product", $_GET) || !is_numeric($_GET["product"]) || $this->item->getSeller() === null) {
            $this->redirectToIndex();
        }
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function handleRequestParameters() {
        parent::handleRequestParameters();
        $this->item = new Item($this->databaseHelper, $_GET["product"]);

        if(array_key_exists("bid-on-product", $_POST)) {
            $this->bidOnItem();
        } else if(array_key_exists("feedbackKind", $_POST)) {
            $this->addFeedback();
        }
    }

    private function addFeedback() {
        if (!empty($_POST["feedbackKind"])) {
            if ($this->item->getSellerId() === $this->loggedInUser->getUsername()) {
                $kindOfUser = "seller";
            } else {
                $kindOfUser = "buyer";
            }


            $feedback = new Feedback($this->databaseHelper, $kindOfUser, $this->item->getId());
            if (!empty($_POST["feedbackText"])) {
                $feedback->setComment($_POST["feedbackText"]);
            }

            $feedbackType = $_POST["feedbackKind"];
            $feedback->setKindOfUser(Feedback::$KIND_OF_USERS_TYPES["seller"]);
            $feedback->setFeedbackKind(Feedback::$KIND_OF_FEEDBACK_TYPES["positive"]);

            $feedback->save();
            $this->item->addFeedback($feedback);
        } else {
            //TODO: show error
        }
    }

    private function bidOnItem() {
        $user = $this->userHelper->getLoggedInUser();
        $_POST['bid-amount'] = str_replace(",", ".", $_POST['bid-amount']);
        if(is_numeric($_POST['bid-amount']) && floatval($_POST['bid-amount']) <= 2000000) {
            if ($user !== null) {
                if ($this->item->getSeller()->getUser()->getUsername() !== $user->getUsername()) {
                    $bid = new Bid($this->databaseHelper, $_POST['bid-amount'], $this->item->getId());
                    $bid->setUser($user);
                    if ($bid->save() === false) {
                        $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Bieding niet geplaatst", "Bieding is niet hoog genoeg."));
                    } else {
                        $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Bieding geplaatst", "Uw bieding is geplaatst."));
                    }
                } else {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Bieding niet geplaatst", "U kunt niet op uw eigen veilingen bieden."));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Bieding niet geplaatst", "U bent niet ingelogd."));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Bieding niet geplaatst", "De door uw ingevulde bieding is geen getal of is groter dan &euro;2.000.000."));
        }
    }

    public function createHTML()
    {
        $imageHelper = new ImageHelper();
        $interval = $this->item->getAuctionEndDateTime()->diff(new DateTime());

        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-productoverzicht.html");
        $thumbnail = new HTMLParameter($this->HTMLBuilder, "product\\product-thumbnail.html");
        $feedback = new HTMLParameter($this->HTMLBuilder, "product\\product-feedback.html");
        $enterFeedback = new HTMLParameter($this->HTMLBuilder, "product\\enter-feedback.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        //getting all information from the product
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("title", $this->item->getTitle());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("description", $this->item->getDescription());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("seller", $this->item->getSellerId());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("auction-enddate", $this->item->getAuctionStartDateTime()->format('Y-m-d H:i'));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("payment-instruction", $this->item->getPaymentInstruction());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("payment-method", $this->item->getPaymentMethod());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("shipping-instruction", $this->item->getShippingInstruction());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("shipping-cost", number_format((float)$this->item->getShippingCost(), 2, '.', ''));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("bid-container", $this->generateBidTemplates());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("product-feedback", $feedback);

        //if ($this->item->getIsAuctionClosed()) {
        $feedbacks = $this->item->getFeedbacks()->getAllFeedback();
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("feedback-buyer", $enterFeedback);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("feedback-seller", "<h4>de verkoper heeft nog geen feedback gegeven</h4>");
        foreach ($feedbacks as $customerFeedback) {
            if (($customerFeedback !== null) && ($this->loggedInUser!== null)) {
                if($this->loggedInUser->getUsername() == $this->item->getSellerId()){
                    $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("feedback-seller", $enterFeedback);
                }
                if($this->loggedInUser->getUsername() == $this->item->getBuyerId()){
                    $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("feedback-buyer", $enterFeedback);
                }
            }
        }
        //}





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

        if(!$this->loggedInUser === null){
            if ($this->loggedInUser->getUsername() === $this->item->getSeller()->getUser()->getUsername()){
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "disabled");
            }
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("is-disabled", "disabled");
        }

        $images = $this->item->getImages();
        if(count($images) >= 1) {
            foreach ($images as $index => $image) {
                $imagePath = $imageHelper->getImageLocation($image);

                if ($index == 0) {
                    $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("img-source", $imagePath);
                }
                if ($index >= 1) {
                    if (strpos($imagePath, 'pics') !== false) {
                        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("thumbnails", $thumbnail);
                        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("img-source-thumb", $imagePath);
                    }
                }
            }
        } else {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("img-source", ImageHelper::$NO_FILE_FOUND_LOCATION);
        }

        $this->processHighestBid();
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
        
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
            $bidTemplate->addTemplateParameterByString("amount", number_format((float)$bid->getAmount(), 2, '.', ''));
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
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("minimal-new-value", number_format(($highestPrice + $minimalIncrement), 2, '.', ''));
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