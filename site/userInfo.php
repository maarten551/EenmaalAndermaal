<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Models\Item;
use src\classes\Models\Seller;
use src\classes\Models\UserPhoneNumber;
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
        $seller = new Seller($this->databaseHelper, $this->loggedInUser);
        $seller->getActivationCode();
        if(array_key_exists('become-seller-button', $_POST) && $this->loggedInUser->isSeller() === false) {
            $this->requestSellerStatus();
        } else if (array_key_exists('activate-seller-account', $_POST) && $this->loggedInUser->isSeller() === true && (new Seller($this->databaseHelper, $this->loggedInUser))->getActivationCode() !== null) {
            $this->activateSellerAccount(array_key_exists('activate-seller-account', $_POST));
        } else if (array_key_exists("add-phone-number", $_POST)) {
            $this->addPhoneNumber();
        } else if (array_key_exists("phone-number-delete", $_POST)) {
            $this->removePhoneNumber();
        } else if (array_key_exists("change-or-save", $_POST) && $_POST['change-or-save'] === "Opslaan") {
            $this->updateUser();
        }
    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-user.html");
        $activateSellerCodeModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\activate-sellercode-modal.html");
        $createSellerModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\create-selleraccount-modal.html");
        $changePasswordModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\change-password-modal.html");
        $phoneNumberModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\phonenumber-modal.html");

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("change-password", $changePasswordModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("manage-phonenumbers", $phoneNumberModal);

        if ($this->loggedInUser->isSeller() === false) {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("create-seller", $createSellerModal);
        } else if ($this->loggedInUser->isSeller() === true && (new Seller($this->databaseHelper, $this->loggedInUser))->getActivationCode() !== null) {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("activate-sellercode", $activateSellerCodeModal);
        }

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
        $this->generatePhoneNumberTemplate($phoneNumberModal);
        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }

    private function activateSellerAccount() {
        $seller = new Seller($this->databaseHelper, $this->loggedInUser);
        if($seller->getActivationCode() === $_POST['activate-seller-account-code']) {
            $seller->setActivationCode(null);
            $this->activateProducts($seller);
            $seller->save();
            $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Verifiëren voltooid", "U heeft nu een verkoper status"));
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Verifiëren mislukt", "De opgegeven code komt niet overeen met de benodigde code"));
        }
    }

    private function generatePhoneNumberTemplate(HTMLParameter $modalTemplate) {
        $phoneNumbers = $this->loggedInUser->getPhoneNumbers();
        if(count($phoneNumbers) >= 1) {
            $optionQuestionHTML = "";
            foreach ($phoneNumbers as $phoneNumber) {
                $optionQuestionHTML .= "<option value='" . $phoneNumber->getPhoneNumber() . "'>" . $phoneNumber->getPhoneNumber() . "</option>";
            }
            $modalTemplate->addTemplateParameterByString("phone-numbers", $optionQuestionHTML);
        } else {
            $modalTemplate->addTemplateParameterByString("delete-phone-number-is-disabled", "disabled");
        }


    }

    private function placeUserDataInForm(HTMLParameter $template) {
        $template->addTemplateParameterByString("username", $this->loggedInUser->getUsername());
        $template->addTemplateParameterByString("email", $this->loggedInUser->getMailbox());
        $template->addTemplateParameterByString("firstname", $this->loggedInUser->getFirstname());
        $template->addTemplateParameterByString("lastname", $this->loggedInUser->getLastname());
        $template->addTemplateParameterByString("zipcode", $this->loggedInUser->getZipCode());
        $template->addTemplateParameterByString("country", $this->loggedInUser->getCountry());
        $template->addTemplateParameterByString("adress", htmlentities($this->loggedInUser->getFirstAddress()));
        $template->addTemplateParameterByString("second-adress", $this->loggedInUser->getSecondAddress());
        $template->addTemplateParameterByString("town", $this->loggedInUser->getTown());
        $template->addTemplateParameterByString("birthdate", $this->loggedInUser->getBirthdate()->format("m/d/Y"));
    }

    private function updateUser()
    {
        $userFields = array(
            "firstname" => "required",
            "lastname" => "required",
            "firstAddress" => "required",
            "zipCode" => "required",
            "town" => "required",
            "country" => "required",
            "birthdate" => "required",
            "mailbox" => "required",
            "phoneNumber" => "optional",
            "secondAddress" => "optional"
        );

        if($this->checkAllRequiredFields($userFields) === true) {
            $birthDate = null;
            try {
                $birthDate = \DateTime::createFromFormat("d/m/Y", $_POST['birthdate']);
            } catch(\Exception $e) {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Gebruiker aanpassingen niet toegepast", "De ingevulde datum is niet correct ingevuld."));
            }

            if($birthDate !== null && $birthDate !== false) {
                $this->loggedInUser->getQuestionId();
                $this->loggedInUser->setFirstname($_POST['firstname']);
                $this->loggedInUser->setLastname($_POST['lastname']);
                $this->loggedInUser->setBirthdate($birthDate);
                $this->loggedInUser->setFirstAddress($_POST['firstAddress']);
                $this->loggedInUser->setSecondAddress($_POST['secondAddress']);
                $this->loggedInUser->setMailbox($_POST['mailbox']);
                $this->loggedInUser->setCountry($_POST['country']);
                $this->loggedInUser->setTown($_POST['town']);
                $this->loggedInUser->setZipCode($_POST['zipCode']);
                $this->loggedInUser->save();
                $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Gebruiker aanpassingen toegepast", "Uw account is aangepast"));
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Gebruiker aanpassingen niet toegepast", "De ingevulde datum is niet correct ingevuld."));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Gebruiker aanpassingen niet toegepast", "Niet alle velden zijn correct ingevuld"));
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
                if(array_key_exists("become-seller-credit-card-number", $_POST)) {
                    $seller->setCreditcardNumber($_POST["become-seller-credit-card-number"]);
                }
                $seller->setControlOption($controlOption);
                $seller->getUser()->setIsSeller(true);
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
            $username = $seller->getUser()->getUsername();
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), "{call sp_resetAuctionsBySeller (?) }", array(&$username));

            if(sqlsrv_execute($statement) === false) {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Producten niet gereset", "De al voor u ingevoerde producten zijn niet gestart vanwege een onbekende probleem"));
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

    private function addPhoneNumber()
    {
        if(array_key_exists("add-phone-number-value", $_POST) === true && !empty($_POST['add-phone-number-value'])) {
            $phoneNumber = new UserPhoneNumber($this->databaseHelper);
            if($this->doesPhoneNumberAlreadyExists($_POST['add-phone-number-value']) === false) {
                $phoneNumber->setUser($this->loggedInUser);
                $phoneNumber->setPhoneNumber($_POST['add-phone-number-value']);
                $phoneNumber->save();
                if($phoneNumber->getId() !== null) {
                    $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Telefoonnummer toegevoegd", "uw telefoonnummer is toegevoegd aan uw account"));
                } else {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Telefoonnummer niet toegevoegd", "Er is een onbekende probleem voorgekomen"));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Telefoonnummer niet toegevoegd", "U heeft dit telefoonnummer al toegevoegd"));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Telefoonnummer niet toegevoegd", "Er is geen telefoonnummer meegegeven."));
        }
    }

    private function removePhoneNumber()
    {
        if(array_key_exists("phone-number-delete-value", $_POST) === true && !empty($_POST['phone-number-delete-value'])) {
            $phoneNumber = $this->doesPhoneNumberAlreadyExists($_POST['phone-number-delete-value']);
            if($phoneNumber !== false) {
                $phoneNumber->delete();
                if($phoneNumber->getId() === null) {
                    $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Telefoonnummer toegevoegd", "uw telefoonnummer is verwijderd van uw account"));
                } else {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Telefoonnummer niet verwijderd", "Er is een onbekende probleem voorgekomen"));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Telefoonnummer niet verwijderd", "De meegegeven telefoonnummer is niet in uw account"));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Telefoonnummer niet verwijderd", "Er is geen telefoonnummer meegegeven."));
        }
    }

    /**
     * @param $newPhoneNumber
     * @return bool|UserPhoneNumber
     */
    private function doesPhoneNumberAlreadyExists($newPhoneNumber) {
        $newPhoneNumber = trim($newPhoneNumber);
        $phoneNumbers = $this->loggedInUser->getPhoneNumbers();
        foreach ($phoneNumbers as $phoneNumber) {
            if($phoneNumber->getPhoneNumber() === $newPhoneNumber) {
                return $phoneNumber;
            }
        }

        return false;
    }
}

$template = new UserInfo();
echo $template->createHTML();