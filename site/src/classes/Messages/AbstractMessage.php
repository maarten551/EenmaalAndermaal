<?php
namespace src\classes\Messages;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;

class AbstractMessage {
    protected $title;
    protected $message;
    protected $HTMLTemplateLocation;
    /** @var HTMLBuilder */
    protected $HTMLBuilder;

    public function toHTMLParameter() {
        $HTMLParameter = new HTMLParameter($this->HTMLBuilder, $this->HTMLTemplateLocation);
        $HTMLParameter->addTemplateParameterByString("message-title", $this->title);
        $HTMLParameter->addTemplateParameterByString("message-description", $this->title);

        return $HTMLParameter;
    }
}