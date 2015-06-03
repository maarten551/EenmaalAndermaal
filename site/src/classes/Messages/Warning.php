<?php
namespace src\classes\Messages;
use src\classes\HTMLBuilder;
use src\classes\Messages\AbstractMessage;

class Warning extends AbstractMessage {
    public function __construct(HTMLBuilder $HTMLBuilder, $title = "Warning!", $message = "This is a warning!") {
        $this->HTMLBuilder = $HTMLBuilder;
        $this->HTMLTemplateLocation = "messages/warning.html";
        $this->title = $title;
        $this->message = $message;
    }
}