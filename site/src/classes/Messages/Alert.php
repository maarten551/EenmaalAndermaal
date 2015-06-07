<?php
namespace src\classes\Messages;
use src\classes\HTMLBuilder;
use src\classes\Messages\AbstractMessage;
class Alert extends AbstractMessage {
    public function __construct(HTMLBuilder $HTMLBuilder, $title = "Alert!", $message = "Test message") {
        $this->HTMLBuilder = $HTMLBuilder;
        $this->HTMLTemplateLocation = "messages/alert.html";
        $this->title = $title;
        $this->message = $message;
    }
}