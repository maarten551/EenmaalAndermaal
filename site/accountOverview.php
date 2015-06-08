<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\User;
use src\classes\Page;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
    require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class accountoverview extends Page
{

    public function __construct()
    {
        parent::__construct("template.html");
    }

    public function createHTML()
    {
        $this->user = new User($this->databaseHelper, $_GET["user"]);

        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-account-overview.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $feedbackTemplate = new HTMLParameter($this->HTMLBuilder, "content\\feedback\\feedback-template.html");

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("positive-feedback", $feedbackTemplate);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("negative-feedback", $feedbackTemplate);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("username", $this->user->getUsername());
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("country", $this->user->getCountry());

        $givenFeedback = $this->createGivenFeedbackTemplate($this->user->getFeedbacks()->getAllFeedback());
        $receivedFeedback = $this->createReceivedFeedbackTemplate($this->user->getFeedbacks()->getAllFeedback());

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("count-given", sizeof($givenFeedback));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("count-received", sizeof($receivedFeedback));

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("given-feedback",$this->HTMLBuilder->joinHTMLParameters($givenFeedback));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("received-feedback",$this->HTMLBuilder->joinHTMLParameters($receivedFeedback));

        $this->generateLoginAndRegisterTemplates();
        return $this->HTMLBuilder->getHTML();
    }


    public function __destruct()
    {
        parent::__destruct();
    }


    public function createReceivedFeedbackTemplate($feedbackKind)
    {
        $feedbackTemplates = array();
        foreach ($feedbackKind as $feedback) {
            if ($feedback->getUser()->getUsername() !== $this->user->getUsername()) {
                $feedbackTemplates[] = $this->generateFeedbackTemplate($feedback);
            }
        }
        return $feedbackTemplates;
    }

    public function createGivenFeedbackTemplate($feedbackKind)
    {
        $feedbackTemplates = array();
        foreach ($feedbackKind as $feedback) {
            if ($feedback->getUser()->getUsername() === $this->user->getUsername()) {
                $feedbackTemplates[] = $this->generateFeedbackTemplate($feedback);
            }
        }
        return $feedbackTemplates;
    }

    private function generateFeedbackTemplate($feedback)
    {
        $feedbackTemplate = new HTMLParameter($this->HTMLBuilder, "content\\feedback\\feedback-template.html");
        $placementDate = $feedback->getPlacementDateTime();
        $feedbackTemplate->addTemplateParameterByString("username-feedbackgiver", $feedback->getUser()->getUsername());
        $feedbackTemplate->addTemplateParameterByString("is-seller", $feedback->getKindOfUser());
        $feedbackTemplate->addTemplateParameterByString("title", $feedback->getItem()->getTitle());
        $feedbackTemplate->addTemplateParameterByString("placement-date", $placementDate->format('Y-m-d'));
        $feedbackTemplate->addTemplateParameterByString("feedback-text", $feedback->getComment());
        if($feedback->getFeedbackKind() == "positive") {
            $feedbackTemplate->addTemplateParameterByString("feedback-type", '<text style="color: green">Positief</text>');
        } else {
            $feedbackTemplate->addTemplateParameterByString("feedback-type", '<text style="color: red">Negatief</text>');
        }

        return $feedbackTemplate;
    }
}

$page = new accountOverview();
echo $page->createHTML();