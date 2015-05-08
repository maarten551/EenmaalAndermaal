<?php
/**
 * Created by PhpStorm.
 * User: Maarten Bobbeldijk
 * Date: 18/2/2015
 * Time: 0:01 AM
 */

namespace src\classes\Pages;
use src\classes\HTMLBuilder\HTMLParameter;

abstract class AbstractPage {
    protected $pageName;
    protected $parameters;

    abstract protected function createHTML();

    protected function __construct($parameters) {
        $explodedParameters = explode("/", $parameters);
        $this->pageName = $explodedParameters[0];
        $this->parameters = $explodedParameters;
        array_shift($this->parameters); //Removes the pageName from the array
    }

    public function getPageName() {
        return $this->pageName;
    }
}