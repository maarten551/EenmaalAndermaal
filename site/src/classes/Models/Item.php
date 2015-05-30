<?php


namespace src\classes\Models;


use src\classes\DatabaseHelper;

class Item extends Model {
    protected $id;
    /**
     * @var string
     * Id of buyer, name needs be the same as in the database
     */
    protected $buyer;
    /**
     * @var User
     * Object of buyer found by id 'buyer'
     */
    protected $buyerObject;
    /**
     * @var string
     * Id of seller, name needs be the same as in the database
     */
    protected $seller;
    /**
     * @var User
     * Object of seller found by id 'seller'
     * TODO: Create the class 'Seller'
     */
    protected $sellerObject;
    protected $title;
    protected $description;
    protected $startPrice;
    protected $paymentMethod;
    protected $paymentInstruction;
    protected $town;
    protected $country;
    protected $shippingCost;
    protected $shippingInstruction;
    protected $auctionDurationInDays;
    /**
     * @var \DateTime
     */
    protected $auctionStartDateTime;
    /**
     * @var \DateTime
     */
    protected $auctionEndDateTime;
    protected $isAuctionClosed = 0;
    protected $sellPrice;


    public function __construct(DatabaseHelper $databaseHelper, $primaryKeyValue = null) {
        parent::__construct($databaseHelper);
        $this->tableName = "Item";
        $this->primaryKeyName = "id";
        $this->hasIdentity = true;
        $this->databaseFields["required"]["seller"] = "quote";
        $this->databaseFields["required"]["title"] = "quote";
        $this->databaseFields["required"]["description"] = "quote";
        $this->databaseFields["required"]["startPrice"] = "quote";
        $this->databaseFields["required"]["paymentMethod"] = "quote";
        $this->databaseFields["required"]["town"] = "quote";
        $this->databaseFields["required"]["country"] = "quote";
        $this->databaseFields["required"]["auctionDurationInDays"] = "quote";
        $this->databaseFields["required"]["auctionStartDateTime"] = "quote";
        $this->databaseFields["required"]["auctionEndDateTime"] = "quote";
        $this->databaseFields["required"]["isAuctionClosed"] = "quote";

        $this->databaseFields["optional"]["buyer"] = "quote";
        $this->databaseFields["optional"]["paymentInstruction"] = "quote";
        $this->databaseFields["optional"]["shippingCost"] = "quote";
        $this->databaseFields["optional"]["shippingInstruction"] = "quote";
        $this->databaseFields["optional"]["sellPrice"] = "quote";

        $this->setId($primaryKeyValue);
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

    /**
     * @return string
     */
    public function getBuyerId()
    {
        $this->get("buyer");
    }

    /**
     * @param $buyerId
     */
    public function setBuyerId($buyerId)
    {
        $this->set("buyer", $buyerId);
    }

    /**
     * @return User
     */
    public function getBuyer()
    {
        if($this->buyerObject === null && $this->getBuyerId() !== null) {
            $this->buyerObject = new User($this->databaseHelper, $this->getBuyerId());
        }

        return $this->buyerObject;
    }

    /**
     * @param User $buyer
     */
    public function setBuyer($buyer)
    {
        if($buyer !== null && $buyer instanceof User) {
            $this->buyerObject = $buyer;
            $this->buyer = $buyer->getId();
        }
    }

    /**
     * @return string
     */
    public function getSellerId()
    {
        return $this->get("seller");
    }

    /**
     * @param string $sellerId
     */
    public function setSellerId($sellerId)
    {
        $this->set("seller", $sellerId);
    }

    /**
     * @return User
     */
    public function getSeller()
    {
        if($this->sellerObject === null && $this->getSellerId() !== null) {
            $this->sellerObject = new User($this->databaseHelper, $this->getSellerId());
        }

        return $this->sellerObject;
    }

    /**
     * @param User $seller
     */
    public function setSeller($seller)
    {
        //TODO: When the class 'Seller' is created, replace this
        if($seller !== null && $seller instanceof User) {
            $this->sellerObject = $seller;
            $this->seller = $seller->getId();
        }
    }

    /**
     * @return File[]
     */
    public function getImages() {
        /**
         * @var $images File[]
         */
        $images = array();
        if($this->getId() !== null) {
            $selectQuery = "SELECT fileName, itemId FROM [file] WHERE itemId = ?";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $selectQuery, array(&$this->id));
            sqlsrv_execute($statement);

            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $images[] = new File($this->databaseHelper, $row["fileName"], $row["itemId"]);
            }
        }

        return $images;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->get("title");
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->set("title", $title);
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->get("description");
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->set("description", $description);
    }

    /**
     * @return mixed
     */
    public function getStartPrice()
    {
        return $this->get("");
    }

    /**
     * @param mixed $startPrice
     */
    public function setStartPrice($startPrice)
    {
        $this->get("startPrice", $startPrice);
    }

    /**
     * @return mixed
     */
    public function getPaymentMethod()
    {
        return $this->get("paymentMethod");
    }

    /**
     * @param mixed $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->set("paymentMethod", $paymentMethod);
    }

    /**
     * @return mixed
     */
    public function getPaymentInstruction()
    {
        return $this->get("paymentInstruction");
    }

    /**
     * @param mixed $paymentInstruction
     */
    public function setPaymentInstruction($paymentInstruction)
    {
        $this->set("paymentInstruction", $paymentInstruction);
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
     * @return mixed
     */
    public function getShippingCost()
    {
        return $this->get("shippingCost");
    }

    /**
     * @param mixed $shippingCost
     */
    public function setShippingCost($shippingCost)
    {
        $this->set("shippingCost", $shippingCost);
    }

    /**
     * @return mixed
     */
    public function getShippingInstruction()
    {
        return $this->get("shippingInstruction");
    }

    /**
     * @param mixed $shippingInstruction
     */
    public function setShippingInstruction($shippingInstruction)
    {
        $this->set("shippingInstruction", $shippingInstruction);
    }

    /**
     * @return mixed
     */
    public function getAuctionDurationInDays()
    {
        return $this->get("auctionDurationInDays");
    }

    /**
     * @param mixed $auctionDurationInDays
     */
    public function setAuctionDurationInDays($auctionDurationInDays)
    {
        $this->set("auctionDurationInDays", $auctionDurationInDays);
    }

    /**
     * @return \DateTime
     */
    public function getAuctionStartDateTime()
    {
        return $this->get("auctionStartDateTime");
    }

    /**
     * @param \DateTime $auctionStartDateTime
     */
    public function setAuctionStartDateTime($auctionStartDateTime)
    {
        $this->set("auctionStartDateTime", $auctionStartDateTime);
    }

    /**
     * @return \DateTime
     */
    public function getAuctionEndDateTime()
    {
        return $this->get("auctionEndDateTime");
    }

    /**
     * @param \DateTime $auctionEndDateTime
     */
    public function setAuctionEndDateTime($auctionEndDateTime)
    {
        $this->set("auctionEndDateTime", $auctionEndDateTime);
    }

    /**
     * @return mixed
     */
    public function getIsAuctionClosed()
    {
        return $this->get("isAuctionClosed");
    }

    /**
     * @param mixed $isAuctionClosed
     */
    public function setIsAuctionClosed($isAuctionClosed)
    {
        $this->set("isAuctionClosed", $isAuctionClosed);
    }


}