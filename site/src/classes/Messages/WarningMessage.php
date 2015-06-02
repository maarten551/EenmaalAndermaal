<?php
namespace src\classes\Messages;
use src\classes\HTMLBuilder;
use src\classes\Messages\AbstractMessage;

class Warning extends AbstractMessage {
    public function __construct(HTMLBuilder $HTMLBuilder) {
        $this->HTMLBuilder = $HTMLBuilder;
        $this->HTMLTemplateLocation = "messages/warning.html";
        $this->title = "Alert!";
        $this->message = "Test message";
    }
}