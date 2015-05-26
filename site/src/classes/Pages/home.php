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
        $mobileMenu= new HTMLParameter($this->HTMLBuilder, "product\\mobile-category.html");
        $desktopMenu= new HTMLParameter($this->HTMLBuilder, "product\\desktop-category.html");
        $category = new HTMLParameter($this->HTMLBuilder, "product\\category.html");
        $testString = array("Deze beschrijving is te lang en moet afgekort worden", "korte beschrijving");
        $randomElement = $testString[array_rand($testString, 1)];


        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("product", $product);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("titel", $randomElement);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("mobile-category", $mobileMenu);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("desktop-category", $desktopMenu);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("category", $category);
        echo $this->HTMLBuilder->getHTML();
    }
}