<?php

namespace src\classes\Models;

use src\classes\DatabaseHelper;
use src\classes\FeedbackCollection;

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
    /**
     * @var UserPhoneNumber[]
     */
    protected $phoneNumbers = array();
    /**
     * @var Bid[]
     */
    protected $bids = array();
    /**
     * @var FeedbackCollection
     */
    protected $feedbackCollection;

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

        $this->feedbackCollection = new FeedbackCollection();
        $this->setUsername($primaryKeyValue);
    }

    /**
     * @param $feedback Feedback
     */
    public function addFeedback($feedback)
    {
        if ($feedback->getUser() !== $this) {
            $feedback->setUser($this);
        }
        $this->feedbackCollection->addFeedback($feedback);
    }

    /**
     * @return FeedbackCollection
     */
    public function getFeedbacks() {
        if(count($this->feedbackCollection->getAllFeedback()) <= 1 && $this->username !== null) {
            $selectQuery = "SELECT t3.kindOfUser, t3.itemId, t3.feedbackKind, t3.placementDateTime, t3.comment
                            FROM [user] AS t1
                            INNER JOIN [item] AS t2
                                ON (t2.buyer = ? AND t2.buyer = t1.username) OR (t2.seller = ? AND t2.seller = t1.username)
                            INNER JOIN [feedback] AS t3
                                ON t3.itemId = t2.id";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $selectQuery, array(
                array(&$this->username, SQLSRV_PARAM_IN),
                array(&$this->username, SQLSRV_PARAM_IN)
            ));

            if (!sqlsrv_execute($statement)) {
                die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to update
            }

            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                /** @var $feedback Feedback */
                $feedback = new Feedback($this->databaseHelper, $row['kindOfUser'], $row['itemId']);
                $feedback->mergeQueryData($row);
                $this->addFeedback($feedback);
            }
        }

        return $this->feedbackCollection;
    }

    /**
     * @return UserPhoneNumber
     */
    public function getPhoneNumbers() {
        if(count($this->phoneNumbers) === 0 && $this->username !== null) {
            $selectQuery = "SELECT id, username, phoneNumber FROM [userPhoneNumber] WHERE username = ?";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $selectQuery, array(
                array(&$this->username, SQLSRV_PARAM_IN)
            ));

            if (!sqlsrv_execute($statement)) {
                die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to update
            }

            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                /** @var $phoneNumber UserPhoneNumber */
                $phoneNumber = new UserPhoneNumber($this->databaseHelper, $row['id']);
                $phoneNumber->mergeQueryData($row);
                $this->addPhoneNumber($phoneNumber);
            }
        }

        return $this->phoneNumbers;
    }

    /**
     * @param $phoneNumber UserPhoneNumber
     */
    public function addPhoneNumber($phoneNumber) {
        if(array_search($phoneNumber, $this->phoneNumbers, true) === false) {
            $this->phoneNumbers[] = $phoneNumber;
            $phoneNumber->setUser($this);
        }
    }

    /**
     * @return Bid
     */
    public function getBids() {
        if(count($this->bids) === 0 && $this->username !== null) {
            $selectQuery = "SELECT amount, itemId, username, placementDateTime FROM [bid] WHERE username = ? ORDER BY amount DESC";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $selectQuery, array(
                array(&$this->username, SQLSRV_PARAM_IN)
            ));

            if (!sqlsrv_execute($statement)) {
                die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to update
            }

            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                /** @var $bid Bid */
                $bid = new Bid($this->databaseHelper, $row['amount'], $row['itemId']);
                $bid->mergeQueryData($row);
                $this->addBid($bid);
            }
        }

        return $this->bids;
    }

    /**
     * @param $bid Bid
     */
    public function addBid($bid) {
        if(array_search($bid, $this->bids, true) === false) {
            $this->bids[] = $bid;
            $bid->setUser($this);
        }
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