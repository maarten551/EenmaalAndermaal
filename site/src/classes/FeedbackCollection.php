<?php
 

namespace src\classes;


use src\classes\Models\Feedback;

/**
 * Class FeedbackCollection
 * @package src\classes
 *
 * This class is simply to index all the feedback
 */
class FeedbackCollection {
    /**
     * @var Feedback[]
     */
    private $feedbacks = array();
    /**
     * @var array[string][Feedback]
     */
    private $feedbackType = array(
        "positive" => array(),
        "negative" => array()
    );

    /**
     * @param $feedback Feedback
     */
    public function addFeedback($feedback) {
        if($feedback !== null) {
            $arrayKey = $feedback->getItemId() . "-" . $feedback->getKindOfUser();
            if (!array_key_exists($arrayKey, $this->feedbacks)) {
                $this->feedbacks[$arrayKey] = $feedback;
                if ($feedback->getFeedbackKind() === Feedback::$KIND_OF_FEEDBACK_TYPES["negative"]) {
                    $this->feedbackType["negative"][] = $feedback;
                } else {
                    $this->feedbackType["positive"][] = $feedback;
                }
            }
        }
    }

    /**
     * @return Feedback[]
     */
    public function getAllFeedback() {
        return $this->feedbacks;
    }

    /**
     * @return Feedback[]
     */
    public function getPositiveFeedback() {
        return $this->feedbackType["positive"];
    }

    /**
     * @return Feedback[]
     */
    public function getNegativeFeedback() {
        return $this->feedbackType["negative"];
    }
}