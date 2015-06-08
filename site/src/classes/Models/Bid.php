<?php


namespace src\classes\Models;


use src\classes\DatabaseHelper;

class Bid extends Model {
    protected $amount;
    protected $itemId;
    /**
     * @var Item
     */
    protected $item;
    protected $username;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var \DateTime
     */
    protected $placementDateTime;

    /**
     * @param $databaseHelper DatabaseHelper
     * @param $amount
     * @param $itemId
     */
    public function __construct(DatabaseHelper $databaseHelper, $amount, $itemId) {
        parent::__construct($databaseHelper);
        $this->tableName = "bid";
        $this->primaryKeyName = array("amount", "itemId");
        $this->hasIdentity = true;
        $this->databaseFields["required"]["amount"] = "quote";
        $this->databaseFields["required"]["itemId"] = "quote";
        $this->databaseFields["required"]["username"] = "quote";
        $this->databaseFields["optional"]["placementDateTime"] = "quote";

        $this->amount = $amount;
        $this->itemId = $itemId;
    }

    public function save() {
        if($this->areFieldValuesValid($this->getDatabaseFieldsWithValues()) === true) {
            $parameters = array(
                array($this->itemId, SQLSRV_PARAM_IN),
                array($this->username, SQLSRV_PARAM_IN),
                array($this->amount, SQLSRV_PARAM_IN)
            );
            $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "{call sp_addBidToItem (?, ?, ?)}", $parameters);
            if($statement === false) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * @return Item
     */
    public function getItem()
    {
        $itemId = $this->get("itemId");
        if($this->item === null && $itemId !== null) {
            $this->item = new Item($this->databaseHelper, $itemId);
            $this->item->addBid($this);
        }

        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem($item)
    {
        $this->item = $item;
        $this->item->addBid($this);
        $this->set("itemId", $item->getId());
    }

    /**
     * @return User
     */
    public function getUser()
    {
        $username = $this->get("username");
        if($this->user === null && $username !== null) {
            $this->user = new User($this->databaseHelper, $username);
            $this->user->addBid($this);
        }

        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
        $this->user->addBid($this);
        $this->set("username", $user->getUsername());
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->get("amount");
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->set("amount", $amount);
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->get("itemId");
    }

    /**
     * @param mixed $itemId
     */
    public function setItemId($itemId)
    {
        $this->set("itemId", "$itemId");
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->get("username");
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->set("username", $username);
    }

    /**
     * @return \DateTime
     */
    public function getPlacementDateTime()
    {
        return $this->get("placementDateTime");
    }

    /**
     * @param \DateTime $placementDateTime
     */
    public function setPlacementDateTime($placementDateTime)
    {
        $this->set("placementDateTime", $placementDateTime);
    }


}