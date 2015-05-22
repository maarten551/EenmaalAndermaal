<?php

namespace src\classes\Models;

use src\classes\DatabaseHelper;

class User extends Model {
    protected $username;
    protected $firstName;
    protected $lastName;
    protected $mainAddress;
    protected $secondaryAddress;
    protected $zipCode;
    protected $town;
    protected $country;
    /**
     * @var \DateTime
     */
    protected $birthDate;
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
        $this->databaseFields["required"]["questionText"] = "username";

        $this->questionId = 12;

        $this->setUsername($primaryKeyValue);
    }

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
        $this->questionId = &$question->getId();
    }

    private function setUsername($primaryKeyValue) {
        $this->username = $primaryKeyValue;
    }
}