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

class UserInfo extends Page {

    public function __construct() {
        parent::__construct("template.html");
        if($this->loggedInUser === null) {
            $this->redirectToIndex();
        }
    }
    public function __destruct() {
        parent::__destruct();
    }

    protected function handleRequestParameters() {
        parent::handleRequestParameters();

        if(array_key_exists('become-seller-button', $_POST) && $this->loggedInUser->isSeller() === false) {
            $this->requestSellerStatus();
        } else if (array_key_exists('activate-seller-account', $_POST) && $this->loggedInUser->isSeller() === true && (new Seller($this->databaseHelper, $this->loggedInUser))->getActivationCode() !== "") {
            $seller = new Seller($this->databaseHelper, $this->loggedInUser);
            if($seller->getActivationCode() === $_POST['activate-seller-account-code']) {
                $seller->setActivationCode(null);
                $this->activateProducts($seller);
                $seller->save();
                $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Verifi�ren voltooid", "U heeft nu een verkoper status"));
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Verifi�ren mislukt", "De opgegeven code komt niet overeen met de benodigde code"));
            }
        }
        //TODO: Change user information (Not really important, start other things first)
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
        if ( $_SERVER['REQUEST_METHOD'] == 'POST' AND array_key_exists("disabled", $_POST) === true) {
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

        $this->placeUserDataInForm($content);
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }

    private function placeUserDataInForm(HTMLParameter $template) {
        $template->addTemplateParameterByString("username", $this->loggedInUser->getUsername());
        $template->addTemplateParameterByString("email", $this->loggedInUser->getMailbox());
        $template->addTemplateParameterByString("firstname", $this->loggedInUser->getFirstname());
        $template->addTemplateParameterByString("lastname", $this->loggedInUser->getLastname());
        $template->addTemplateParameterByString("zipcode", $this->loggedInUser->getZipCode());
        $template->addTemplateParameterByString("country", $this->loggedInUser->getCountry());
        $template->addTemplateParameterByString("adress", $this->loggedInUser->getFirstAddress());
        $template->addTemplateParameterByString("second-adress", $this->loggedInUser->getSecondAddress());
        $template->addTemplateParameterByString("town", $this->loggedInUser->getTown());
        $template->addTemplateParameterByString("birthdate", $this->loggedInUser->getBirthdate()->format("m/d/Y"));
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
                if(array_key_exists("become-seller-credit-card-number", $_POST)) {
                    $seller->setCreditcardNumber($_POST["become-seller-credit-card-number"]);
                }
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
            sqlsrv_execute($statement);

            if(sqlsrv_has_rows($statement)) {
                while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                    $item = new Item($this->databaseHelper, $row["id"]);
                    $item->setIsAuctionClosed(false);
                    $item->setAuctionStartDateTime(new \DateTime());
                    $startTimeWithAmountOfDaysDifference = clone $item->getAuctionStartDateTime();
                    $item->setAuctionEndDateTime($startTimeWithAmountOfDaysDifference->modify("+". $item->getAuctionDurationInDays() ." days"));
                    $item->save();
                }
            }
        }
    }

    private function checkAllRequiredFields($requestFields) {
        $checkResult = true;
        foreach ($requestFields as $fieldName => $isRequiredValue) {
            if((!array_key_exists($fieldName, $_POST) && $isRequiredValue === "required") || (empty($_POST[$fieldName]) && $isRequiredValue === "required")) {
                $checkResult = false;
                break;
            }
        }

        return $checkResult;
    }
}

$template = new UserInfo();
echo $template->createHTML();