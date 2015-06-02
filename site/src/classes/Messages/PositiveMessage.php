<?php
namespace src\classes\Messages;
use src\classes\HTMLBuilder;
use src\classes\Messages\AbstractMessage;

class PositiveMessage extends AbstractMessage {
    public function __construct(HTMLBuilder $HTMLBuilder) {
        $this->HTMLBuilder = $HTMLBuilder;
        $this->HTMLTemplateLocation = "messages/positive.html";
        $this->title = "Alert!";
        $this->message = "Test message";
    }
}