<?php
namespace src\classes\Messages;
use src\classes\HTMLBuilder;
use src\classes\Messages\AbstractMessage;

class PositiveMessage extends AbstractMessage {
    public function __construct(HTMLBuilder $HTMLBuilder, $title = "Positive!", $message = "There is an Positieve message") {
        $this->HTMLBuilder = $HTMLBuilder;
        $this->HTMLTemplateLocation = "messages/succes.html";
        $this->title = $title;
        $this->message = $message;
    }
}