<?php

namespace src\classes\Models;

use src\classes\DatabaseHelper;

class User extends Model {
    protected $username;
    protected $firstname;
    protected $lastname;
    protected $firstAddress;
    protected $secondAddress;
    protected $zipCode;
    protected $town;
    protected $country;
    /**
     * @var \DateTime
     */
    protected $birthdate;
    protected $mailbox;
    protected $password;
    protected $questionId;
    /**
     * @var Question
     */
    protected $question;
    protected $questionAnswer;
    /**
     * @var bool
     */
    protected $isSeller;
    protected $loginSession;

    public function __construct(DatabaseHelper $databaseHelper, $primaryKeyValue = null) {
        parent::__construct($databaseHelper);
        $this->tableName = "User";
        $this->primaryKeyName = "username";
        $this->hasIdentity = false;
        $this->databaseFields["required"]["username"] = "quote";
        $this->databaseFields["required"]["firstname"] = "quote";
        $this->databaseFields["required"]["lastname"] = "quote";
        $this->databaseFields["required"]["firstAddress"] = "quote";
        $this->databaseFields["required"]["zipCode"] = "quote";
        $this->databaseFields["required"]["town"] = "quote";
        $this->databaseFields["required"]["country"] = "quote";
        $this->databaseFields["required"]["birthdate"] = "quote";
        $this->databaseFields["required"]["mailbox"] = "quote";
        $this->databaseFields["required"]["password"] = "quote";
        $this->databaseFields["required"]["questionId"] = "quote";
        $this->databaseFields["required"]["questionAnswer"] = "quote";
        $this->databaseFields["required"]["isSeller"] = "quote";

        $this->databaseFields["optional"]["secondAddress"] = "quote";

        $this->setUsername($primaryKeyValue);
    }

    /**
     * @return Question
     */
    public function getQuestion() {
        if($this->question === null && $this->get("questionId") !== null) {
            $this->question = new Question($this->databaseHelper, $this->get("questionId"));
        }

        return $this->question;
    }

    /**
     * @param $question Question
     */
    public function setQuestion($question) {
        $this->question = $question;
        $this->questionId = $question->getId();
    }

    /**
     * @param $primaryKeyValue
     */
    public function setUsername($primaryKeyValue) {
        $this->username = $primaryKeyValue;
    }

    /**
     * @return mixed
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->get("firstname");
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname($firstname)
    {
        $this->set("firstname", $firstname);
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->get("lastname");
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->set("lastname", $lastname);
    }

    /**
     * @return mixed
     */
    public function getFirstAddress()
    {
        return $this->get("firstAddress");
    }

    /**
     * @param mixed $firstAddress
     */
    public function setFirstAddress($firstAddress)
    {
        $this->set("firstAddress", $firstAddress);
    }

    /**
     * @return mixed
     */
    public function getSecondAddress()
    {
        return $this->get("secondAddress");
    }

    /**
     * @param mixed $secondAddress
     */
    public function setSecondAddress($secondAddress)
    {
        if(empty($secondAddress)) {
            $secondAddress = null;
        }
        $this->set("secondAddress", $secondAddress);
    }

    /**
     * @return mixed
     */
    public function getZipCode()
    {
        return $this->get("zipCode");
    }

    /**
     * @param mixed $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->set("zipCode", $zipCode);
    }

    /**
     * @return mixed
     */
    public function getTown()
    {
        return $this->get("town");
    }

    /**
     * @param mixed $town
     */
    public function setTown($town)
    {
        $this->set("town", $town);
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->get("country");
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->set("country", $country);
    }

    /**
     * @return \DateTime
     */
    public function getBirthdate()
    {
        return $this->get("birthdate");
    }

    /**
     * @param \DateTime $birthdate
     */
    public function setBirthdate($birthdate)
    {
        $this->set("birthdate", $birthdate);
    }

    /**
     * @return mixed
     */
    public function getMailbox()
    {
        return $this->get("mailbox");
    }

    /**
     * @param mixed $mailbox
     */
    public function setMailbox($mailbox)
    {
        $this->set("mailbox", $mailbox);
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->get("password");
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->set("password", $password);
    }

    /**
     * @return mixed
     */
    public function getQuestionId()
    {
        return $this->get("questionId");
    }

    /**
     * @param mixed $questionId
     */
    public function setQuestionId($questionId)
    {
        $this->set("questionId", $questionId);
    }

    /**
     * @return mixed
     */
    public function getQuestionAnswer()
    {
        return $this->get("questionAnswer");
    }

    /**
     * @param mixed $questionAnswer
     */
    public function setQuestionAnswer($questionAnswer)
    {
        $this->set("questionAnswer", $questionAnswer);
    }

    /**
     * @return boolean
     */
    public function isSeller()
    {
        return $this->get("isSeller");
    }

    /**
     * @param boolean $isSeller
     */
    public function setIsSeller($isSeller)
    {
        $this->set("isSeller", $isSeller);
    }

    /**
     * @return mixed
     */
    public function getLoginSession()
    {
        return $this->get("loginSession");
    }

    /**
     * @param mixed $loginSession
     */
    public function setLoginSession($loginSession)
    {
        $this->set("loginSession", $loginSession);
    }


}