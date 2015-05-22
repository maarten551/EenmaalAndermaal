<?php
namespace src\classes\Pages;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
class Home extends AbstractPage {
    private $HTMLBuilder;

    public function __construct($parameters) {
        parent::__construct($parameters);
        $this->HTMLBuilder = new HTMLBuilder("template.html");
        $this->createHTML();
    }

    protected function createHTML() {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");
        $product = new HTMLParameter($this->HTMLBuilder, "product\\product-item.html");
        $testString = array("Deze beschrijving is te lang en moet afgekort worden", "korte beschrijving");
        $randomElement = $testString[array_rand($testString, 1)];

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("product", $product);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("titel", $randomElement);
        echo $this->HTMLBuilder->getHTML();
    }
}