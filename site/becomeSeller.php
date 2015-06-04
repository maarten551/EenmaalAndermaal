<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Models\Item;
use src\classes\Models\Seller;
use src\classes\Page;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class BecomeSeller extends Page {
    public function __construct() {
        parent::__construct("template.html");
        if($this->loggedInUser === null || $this->loggedInUser->isSeller() === true) {
            $this->redirectToIndex();
        }
    }

    protected function handleRequestParameters() {
        parent::handleRequestParameters();

        if(array_key_exists('become-seller-button', $_POST)) {
            $this->requestSellerStatus();
        }
    }

    private function requestSellerStatus() {
        $requestFields = array(
            "become-seller-verification-method" => "required",
            "become-seller-bank-name" => "optional",
            "become-seller-bank-account" => "optional",
            "become-seller-credit-card-number" => "optional",
        );

        if($this->checkAllRequiredFields($requestFields) === true) {
            $controlOption = null;
            if(!empty($_POST['become-seller-bank-account'])) {
                $controlOption = Seller::$CONTROL_OPTIONS["bankAccount"];
            } else if (!empty($_POST['become-seller-credit-card-number'])) {
                $controlOption = Seller::$CONTROL_OPTIONS["creditCard"];
            }
            if($controlOption !== null) {
                $seller = new Seller($this->databaseHelper);
                $seller->setUser($this->loggedInUser);
                $seller->setBankName($_POST["become-seller-bank-name"]);
                $seller->setAccountNumber($_POST["become-seller-bank-account"]);
                $seller->setCreditcardNumber($_POST["become-seller-credit-card-number"]);
                $seller->setControlOption($controlOption);
                if($_POST['become-seller-verification-method'] === "over-post") {
                    $seller->setActivationCode($this->userHelper->generateRandomPassword());
                    $this->loggedInUser->save();
                    $seller->save();
                    $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Aanvraag voltooid", "U krijgt binnenkort via de post een brief met de code om uw account te activeren"));
                } else {
                    $this->loggedInUser->save();
                    $seller->save();
                    $this->activateProducts($seller);
                    $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Aanvraag voltooid", "U heeft nu een verkoper status"));
                }

            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Aanvraag niet voltooid", "Er moet een bankaccount of creditkaartnummer meegegeven worden"));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Aanvraag niet voltooid", "Niet alle velden zijn correct ingevuld"));
        }
    }

    private function checkAllRequiredFields($registerFields) {
        $checkResult = true;
        foreach ($registerFields as $fieldName => $isRequiredValue) {
            if(!array_key_exists($fieldName, $_POST) || (empty($_POST[$fieldName]) && $isRequiredValue === "required")) {
                $checkResult = false;
                break;
            }
        }

        return $checkResult;
    }

    /**
     * @param Seller $seller
     */
    private function activateProducts(Seller $seller) {
        if($seller->getUser() !== null) {
            $query = "SELECT t1.*
            FROM [item] AS t1
            INNER JOIN [user] AS t2
              ON t2.username = t1.seller
            WHERE t2.username = ?
            AND t1.isAuctionClosed = 1";
            $username = $seller->getUser()->getUsername();
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $query, array(&$username));

            if(sqlsrv_has_rows($statement)) {
                while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                    $item = new Item($this->databaseHelper, $row["id"]);
                    $item->setIsAuctionClosed(false);
                    $item->setAuctionStartDateTime(new \DateTime());
                    $item->setAuctionEndDateTime((new \DateTime())->modify("+". $item->getAuctionDurationInDays() ." days"));
                    $item->save();
                }
            }
        }
    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "become-seller\\form.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }
}

$page = new BecomeSeller();
echo $page->createHTML();