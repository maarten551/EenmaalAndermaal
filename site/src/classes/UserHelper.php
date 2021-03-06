<?php
namespace src\classes;

use src\classes\Messages\Alert;
use src\classes\Messages\PositiveMessage;
use src\classes\Models\Question;
use src\classes\Models\User;
use src\classes\Models\UserPhoneNumber;

class UserHelper {
    /**
     * @var DatabaseHelper
     */
    private $databaseHelper;
    /**
     * @var HTMLBuilder
     * To add messages when needed.
     */
    private $HTMLBuilder;

    public function __construct($databaseHelper, $HTMLBuilder) {
        $this->databaseHelper = $databaseHelper;
        $this->HTMLBuilder = $HTMLBuilder;
    }

    /**
     * @param int $passwordLength
     * @return string
     */
    public function generateRandomPassword($passwordLength = 16) {
        $possibleCharacters = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890";
        $randomPassword = "";
        while(strlen($randomPassword) < $passwordLength) {
            $randomCharacterIndex = rand(0, strlen($possibleCharacters)-1);
            $randomPassword .=  substr($possibleCharacters, $randomCharacterIndex, 1);
        }

        return $randomPassword;
    }

    /**
     * @param $user User
     */
    public function hashPassword(&$user) {
        $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
    }

    /**
     * @param $user User
     * @param $cleanPassword
     * @return bool
     */
    public function hasSamePasswordAsHash(&$user, $cleanPassword) {
        $hashedPassword = $user->getPassword();
        if($hashedPassword !== null && strlen($hashedPassword) === 60) {
            if(password_verify($cleanPassword, $hashedPassword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $username
     * @param $password
     * @return User
     */
    public function loginUser($username, $password) {
        if(!empty($username)) {
            $user = new User($this->databaseHelper, $username);
            if($this->hasSamePasswordAsHash($user, $password)) {
                $_SESSION['loggedInUsername'] = $username;
                return $user;
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Informatie incorrect", "De ingevulde inlognaam en/of wachtwoord is verkeerd ingevuld"));
            }
        }

        return null;
    }

    /**
     * @return null|User
     */
    public function getLoggedInUser() {
        if(isset($_SESSION['loggedInUsername'])) {
            $user = new User($this->databaseHelper, $_SESSION['loggedInUsername']);
            if($user->getFirstname() !== null) {
                return $user;
            } else {
                unset($_SESSION['loggedInUsername']);
                return null;
            }
        }

        return null;
    }

    public function logoutUser() {
        if(isset($_SESSION['loggedInUsername'])) {
            unset($_SESSION['loggedInUsername']);
        }
    }

    public function registerUser()
    {
        $registerFields = array(
            "username" => "required",
            "firstname" => "required",
            "lastname" => "required",
            "firstAddress" => "required",
            "zipCode" => "required",
            "town" => "required",
            "country" => "required",
            "birthdate" => "required",
            "mailbox" => "required",
            "password" => "required",
            "passwordMatch" => "required",
            "secretQuestionType" => "required",
            "secretQuestionAnswer" => "required",
            "phoneNumber" => "optional",
            "secondAddress" => "optional"
        );

        if($this->checkAllRequiredFields($registerFields)) {
            if($_POST['password'] === $_POST['passwordMatch']) {
                $question = Question::GET_BY_QUESTION_TEXT($this->databaseHelper, $_POST['secretQuestionType']);
                if($question !== null) {
                    $birthDate = null;
                    try {
                        $birthDate = \DateTime::createFromFormat("d/m/Y", $_POST['birthdate']);
                    } catch(\Exception $e) {
                        $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Datum klopt niet", "De ingevulde datum is niet correct ingevuld."));
                    }

                    if($birthDate !== null) {
                        $user = new User($this->databaseHelper, $_POST['username']);
                        if($user->getFirstname() === null) { //Check if user already exists
                            $user->setFirstname($_POST['firstname']);
                            $user->setLastname($_POST['lastname']);
                            $user->setPassword($_POST['password']);
                            $user->setBirthdate($birthDate);
                            $user->setFirstAddress($_POST['firstAddress']);
                            $user->setSecondAddress($_POST['secondAddress']);
                            $user->setMailbox($_POST['mailbox']);
                            $user->setCountry($_POST['country']);
                            $user->setTown($_POST['town']);
                            $user->setZipCode($_POST['zipCode']);
                            $user->setQuestion($question);
                            $user->setQuestionAnswer($_POST['secretQuestionAnswer']);
                            $user->setIsSeller(false);
                            $this->hashPassword($user);
                            $user->save();

                            if($user->getIsLoaded() === true) {
                                if(!empty($_POST['phoneNumber'])) {
                                    $userPhoneNumber = new UserPhoneNumber($this->databaseHelper);
                                    $userPhoneNumber->setUser($user);
                                    $userPhoneNumber->setPhoneNumber($_POST['phoneNumber']);
                                    $userPhoneNumber->save();
                                }
                                $this->HTMLBuilder->addMessage(new PositiveMessage($this->HTMLBuilder, "Gebruiker aangemaakt", "Uw account is succesvol aangemaakt, u kunt nu inloggen met de gekozen informatie"));
                            } else {
                                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Probleem met het cre�ren van de gebruiker", "Er was een onbekende probleem met het cre�ren van de gebruiker."));
                            }
                        } else {
                            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Gebruikersnaam bestaal al", "De ingevulde gebruikersnaam komt overeen met een al bestaande gebruiker."));
                        }
                    }
                } else {
                    $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "beveilingsvraag bestaat niet in databank", "De geselecteerde vraag komt niet overeen met wat in de database staat."));
                }
            } else {
                $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Wachtwoorden niet gelijk", "De ingevulde wachtwoorden komen niet overeen."));
            }
        } else {
            $this->HTMLBuilder->addMessage(new Alert($this->HTMLBuilder, "Veld(en) niet ingevuld", "Er zijn ��n of meerdere velden niet ingevuld."));
        }

        return null;
    }

    public function checkAllRequiredFields($registerFields) {
        $checkResult = true;
        foreach ($registerFields as $fieldName => $isRequiredValue) {
            if(!array_key_exists($fieldName, $_POST) || (empty($_POST[$fieldName]) && $isRequiredValue === "required")) {
                $checkResult = false;
                break;
            }
        }

        return $checkResult;
    }
}