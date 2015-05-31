<?php
namespace src\classes;

use src\classes\Models\User;

class UserHelper {
    /**
     * @var DatabaseHelper
     */
    private $databaseHelper;

    public function __construct($databaseHelper) {
        $this->databaseHelper = $databaseHelper;
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
            }
        }

        return null;
    }

    /**
     * @return null|User
     */
    public function getLoggedInUser() {
        if(isset($_SESSION['loggedInUsername'])) {
            return new User($this->databaseHelper, $_SESSION['loggedInUsername']);
        }

        return null;
    }

    public function logoutUser() {
        if(isset($_SESSION['loggedInUsername'])) {
            unset($_SESSION['loggedInUsername']);
        }
    }
}