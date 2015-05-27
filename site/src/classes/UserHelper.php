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

    /**
     * @param $user User
     */
    public function hashPassword(&$user) {
        /*
         $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
        $hash = crypt($user->getPassword(), '$2y$10$'.$salt.'$');

        $user->setPassword($hash);
         */

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
}