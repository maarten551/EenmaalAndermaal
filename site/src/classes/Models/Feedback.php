<?php
 

namespace src\classes\Models;


use src\classes\DatabaseHelper;

class Feedback extends Model {
    public static $KIND_OF_USERS_TYPES = array("buyer" => "buyer", "seller" => "seller");
    public static $KIND_OF_FEEDBACK_TYPES = array("positive" => "positive", "negative" => "negative");

    protected $kindOfUser;
    protected $itemId;
    /**
     * @var Item
     */
    protected $item;
    protected $feedbackKind;
    protected $comment;
    /**
     * @var \DateTime
     */
    protected $placementDateTime;
    /**
     * @var User
     */
    protected $relatedUser;

    /**
     * @param $databaseHelper DatabaseHelper
     * @param $kindOfUser
     * @param $itemId
     */
    public function __construct(DatabaseHelper $databaseHelper, $kindOfUser, $itemId) {
        parent::__construct($databaseHelper);
        $this->tableName = "feedback";
        $this->primaryKeyName = array("kindOfUser", "itemId");
        $this->hasIdentity = false;
        $this->databaseFields["required"]["kindOfUser"] = "quote";
        $this->databaseFields["required"]["itemId"] = "quote";
        $this->databaseFields["required"]["feedbackKind"] = "quote";
        $this->databaseFields["required"]["placementDateTime"] = "quote";

        $this->databaseFields["optional"]["comment"] = "quote";

        $this->amount = $kindOfUser;
        $this->itemId = $itemId;
    }

    public function save() {
        if($this->getIsLoaded() === false) {
            $this->placementDateTime = new \DateTime();
        }

        parent::save();
    }

    public function getUser() {
        if($this->relatedUser === null) {
            $item = $this->getItem();
            if ($item !== null) {
                if($this->kindOfUser == Feedback::$KIND_OF_USERS_TYPES["seller"]) {
                    $this->relatedUser = $item->getSeller();
                } else if ($this->kindOfUser == Feedback::$KIND_OF_USERS_TYPES["buyer"]) {
                    $this->relatedUser = $item->getBuyer();
                }
            }
        }

        return $this->relatedUser;
    }

    /**
     * @param $user User
     */
    public function setUser($user) {
        if($user !== null) {
            $this->relatedUser = $user;
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
            $this->item->addFeedback($this);
        }

        return $this->item;
    }

    /**
     * @param $item Item
     */
    public function setItem($item)
    {
        $this->item = $item;
        $this->item->addFeedback($this);
        $this->set("itemId", $item->getId());
    }

    /**
     * @return mixed
     */
    public function getKindOfUser()
    {
        return $this->kindOfUser;
    }

    /**
     * @param mixed $kindOfUser
     */
    public function setKindOfUser($kindOfUser)
    {
        $this->set("kindOfUser", $kindOfUser);
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @param mixed $itemId
     */
    public function setItemId($itemId)
    {
        $this->set("itemId", $itemId);
    }

    /**
     * @return mixed
     */
    public function getFeedbackKind()
    {
        return $this->get("feedbackKind");
    }

    /**
     * @param $feedbackKind string
     */
    public function setFeedbackKind($feedbackKind)
    {
        if (array_key_exists($feedbackKind, Feedback::$KIND_OF_USERS_TYPES)) {
            $this->set("feedbackKind", $feedbackKind);
        }
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->get("comment");
    }

    /**
     * @param mixed $comment
     */
    public function setComment($comment)
    {
        $this->set("comment", $comment);
    }

    /**
     * @return \DateTime
     */
    public function getPlacementDateTime()
    {
        return $this->get("placementDateTime");
    }
}