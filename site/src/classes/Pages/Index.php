<?php
namespace src\classes\Pages;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
/**
 * Created by PhpStorm.
 * User: Maarten Bobbeldijk
 * Date: 17/2/2015
 * Time: 23:59 PM
 */

class Index extends AbstractPage {
    private $HTMLBuilder;
    private $products;

    public function __construct($parameters) {
        parent::__construct($parameters);
        $this->HTMLBuilder = new HTMLBuilder("consumer_template.html");
        $this->products = $this->getProducts();
        $this->createHTML();
    }

    private function getProducts() {
        $products = array();
        for($i = 0; $i <= 12; $i++) {
            $products[] = "Henk B Nr. $i";
        }

        return $products;
    }

    protected function createHTML() {
        $HTMLProductListTemplate = new HTMLParameter($this->HTMLBuilder, "product\\product-list-template.html");
        $HTMLProducts = array();

        /** @var $HTMLProducts HTMLParameter[] */
        foreach($this->products as $productIndex => $product) {
            $HTMLProducts[$productIndex] = new HTMLParameter($this->HTMLBuilder, "product/product-list-item.html");
            $HTMLProducts[$productIndex]->addTemplateParameterByString("product-item-name", $product);
            $HTMLProducts[$productIndex]->addTemplateParameterByString("product-item-price", "25,50");
        }

        $HTMLProductListTemplate->addTemplateParameterByString("product-items", $this->HTMLBuilder->joinHTMLParameters($HTMLProducts));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $HTMLProductListTemplate);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("date-year", date("Y"));
        echo $this->HTMLBuilder->getHTML();
    }
}