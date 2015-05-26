<?php
namespace src\classes;

use src\classes\Models\User;

class UserHelper {
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

    public function hashPassword($user) {

    }
}