<?php
 

namespace src\classes;


use src\classes\Models\Item;

/**
 * Class FeedbackCollection
 * @package src\classes
 *
 * This class is simply to index all the feedback
 */
class ItemCollection {
    /**
     * @var Feedback[]
     */
    private $items = array();
    /**
     * @var array[string][Feedback]
     */
    private $itemRelationTypes = array(
        "buyer" => array(),
        "seller" => array()
    );

    /**
     * @param $item Item
     * @param $itemRelationType
     */
    public function addItem($item, $itemRelationType) {
        if($item !== null && !empty($itemRelationType)) {
            $arrayKey = $item->getId() . "-" . $itemRelationType;
            if (!array_key_exists($arrayKey, $this->items)) {
                $this->items[$arrayKey] = $item;
                if ($itemRelationType === "buyer") {
                    $this->itemRelationTypes["buyer"][] = $item;
                } else {
                    $this->itemRelationTypes["seller"][] = $item;
                }
            }
        }
    }

    /**
     * @return Item[]
     */
    public function getAllItems() {
        return $this->items;
    }

    /**
     * @return Item[]
     */
    public function getItemsAsBuyer() {
        return $this->itemRelationTypes["buyer"];
    }

    /**
     * @return Item[]
     */
    public function getItemsAsSeller() {
        return $this->itemRelationTypes["seller"];
    }
}