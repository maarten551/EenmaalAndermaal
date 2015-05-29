<?php
namespace src\classes\Pages;
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Rubric;

class Home extends AbstractPage {
    private $HTMLBuilder;

    public function __construct($parameters) {
        parent::__construct($parameters);
        $this->HTMLBuilder = new HTMLBuilder("template.html");
        $this->createHTML();
    }

    protected function createHTML() {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        echo $this->HTMLBuilder->getHTML();
    }
}