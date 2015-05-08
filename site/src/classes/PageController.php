<?php
/**
 * Created by PhpStorm.
 * User: Maarten Bobbeldijk
 * Date: 17/2/2015
 * Time: 23:39 PM
 */

namespace src\classes;
use src\classes\Pages\Index;

class PageController {
    const PAGE_DIRECTORY = "src/classes/Pages/";
    private $originalUrlParameter;
    private $pageClass;

    public function __construct($urlParameter) {
        $this->originalUrlParameter = $urlParameter;
        $urlParameters = explode("/", $urlParameter);
        if($this->doesPageExist($urlParameters[0])) {
            $classLocation = "\\src\\classes\\Pages\\" . ucfirst($urlParameters[0]); //The autoloader includes this class automatically
            $this->pageClass = new $classLocation($urlParameter);
        } else {
            $this->pageClass = new Index("Index"); //When page is not found, go to the index page
        }
    }

    private function doesPageExist($pageName) {
        $pageClassLocation = str_replace(".", "", self::PAGE_DIRECTORY.ucfirst($pageName)).".php"; //Just in case people try to the parent (../), but I doubt that will even work
        if(file_exists($pageClassLocation)) {
            return true;
        } else {
            return false;
        }
    }
}