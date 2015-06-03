<?php
 

namespace src\classes\Models;

use src\classes\DatabaseHelper;

class Seller extends Model {
    public static $CONTROL_OPTIONS = array("creditCard" => "creditCard", "bankAccount" => "bankAccount");
    protected $username;
    /**
     * @var User
     */
    protected $user;
    protected $bankName;
    protected $accountNumber;
    protected $controlOption;
    protected $creditcardNumber;
    protected $activationCode;

    /**
     * @param DatabaseHelper $databaseHelper
     * @param $user User
     */
    public function __construct(DatabaseHelper $databaseHelper, User $user) {
        parent::__construct($databaseHelper);
        if($user !== null && $user->getUsername() !== null && $user->isSeller() === true) {
            $this->tableName = "Seller";
            $this->primaryKeyName = "username";
            $this->hasIdentity = false;
            $this->databaseFields["required"]["username"] = "quote";
            $this->databaseFields["required"]["controlOption"] = "quote";

            $this->databaseFields["optional"]["bankName"] = "quote";
            $this->databaseFields["optional"]["accountNumber"] = "quote";
            $this->databaseFields["optional"]["creditcardNumber"] = "quote";
            $this->databaseFields["optional"]["activationCode"] = "quote";

            $this->setUser($user);
        }
    }

    /**
     * @param $user User
     */
    public function setUser($user) {
        if($user !== null) {
            $this->username = $user->getUsername();
            $this->user = $user;
        }
    }

    public function getUser() {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getBankName()
    {
        return $this->get("bankName");
    }

    /**
     * @param mixed $bankName
     */
    public function setBankName($bankName)
    {
        $this->set("bankName", $bankName);
    }

    /**
     * @return mixed
     */
    public function getAccountNumber()
    {
        return $this->get("accountNumber");
    }

    /**
     * @param mixed $accountNumber
     */
    public function setAccountNumber($accountNumber)
    {
        $this->set("accountNumber", $accountNumber);
    }

    /**
     * @return mixed
     */
    public function getControlOption()
    {
        return $this->get("controlOption");
    }

    /**
     * @param mixed $controlOption
     */
    public function setControlOption($controlOption)
    {
        $this->set("controlOption", $controlOption);
    }

    /**
     * @return mixed
     */
    public function getCreditcardNumber()
    {
        return $this->get("creditcardNumber");
    }

    /**
     * @param mixed $creditcardNumber
     */
    public function setCreditcardNumber($creditcardNumber)
    {
        $this->set("creditcardNumber", $creditcardNumber);
    }

    /**
     * @return mixed
     */
    public function getActivationCode()
    {
        return $this->get("activationCode");
    }

    /**
     * @param mixed $activationCode
     */
    public function setActivationCode($activationCode)
    {
        $this->set("activationCode", $activationCode);
    }


}