<?php
 

namespace src\classes\Models;


use src\classes\DatabaseHelper;

class UserPhoneNumber extends Model {
    protected $id;
    protected $username;
    protected $phoneNumber;
    /**
     * @var User
     */
    protected $user;

    public function __construct(DatabaseHelper $databaseHelper, $primaryKeyValue = null) {
        parent::__construct($databaseHelper);
        $this->tableName = "UserPhoneNumber";
        $this->primaryKeyName = "id";
        $this->hasIdentity = true;
        $this->databaseFields["required"]["username"] = "quote";
        $this->databaseFields["required"]["phoneNumber"] = "quote";

        $this->setId($primaryKeyValue);
    }

    /**
     * @return null|User
     */
    public function getUser() {
        if($this->user === null && !empty($this->username)) {
            $this->user = new User($this->databaseHelper, $this->username);
            $this->user->addPhoneNumber($this);
        }

        return $this->user;
    }

    /**
     * @param $user User
     */
    public function setUser($user) {
        if($user !== null) {
            $this->username = $user->getUsername();
            $this->user = $user;
            $this->user->addPhoneNumber($this);
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUsername()
    {
        return $this->get("username");
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->set("questionText", $username);
    }

    public function getPhoneNumber() {
        return $this->get("phoneNumber");
    }

    public function setPhoneNumber($phoneNumber) {
        $this->set("phoneNumber", $phoneNumber);
    }


}