<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Bid;
use src\classes\Models\File;
use src\classes\Models\Item;
use \src\classes\ImageHelper;
use src\classes\Models\Question;
use src\classes\Models\Rubric;
use src\classes\Models\Seller;
use src\classes\Models\User;
use src\classes\Page;
use src\classes\ProductPagination;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class Index extends Page {
    private $imageHelper;
    private $productGridTemplateHTML;
    private $productListTemplateHTML;
    private $mobileMenuTemplateHTML;
    private $desktopMenuTemplateHTML;
    private $desktopMenuChildrenTemplateHTML;
    private $previousGetters;

    public function __construct() {
        parent::__construct("template.html");

        $this->imageHelper = new ImageHelper();

        /* Save all HTML files into memory, otherwise every time a new template is loaded, */
        $this->productTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("product\\product-item-list.html");
        $this->productTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("product\\product-item-grid.html");
        $this->productGridTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("product\\product-item-grid.html");
        $this->productListTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("product\\product-item-list.html");
        $this->mobileMenuTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("content\\rubric\\mobile-category.html");
        $this->desktopMenuTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("content\\rubric\\desktop-category.html");
        $this->desktopMenuChildrenTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("content\\rubric\\desktop-child-categories.html");

    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        /* check for every get if it has been set, and if no, give them a default value*/
        if (empty($_GET["productsPerPage"])){
            $productsPerPage = 6;
        } else {
            $productsPerPage = $_GET["productsPerPage"];
            $_SESSION["productsPerPage"] = $_GET["productsPerPage"];
        }
        if (empty($_GET["view"])){
            $view = "grid";
        } else{
            $view = $_GET["view"];
            $_SESSION["view"] = $_GET["view"];
        }
        if (empty($_GET["pageNumber"]) || ($_GET["pageNumber"] <=0)){
            $pageNumber = 1;
        } else {
            $pageNumber = $_GET["pageNumber"];
            $_SESSION["pageNumber"] = $_GET["pageNumber"];
        }
        if (empty($_GET["category"])){
            $category="";
        } else {
            $category = $_GET["category"];
            $_SESSION["category"] = $_GET["category"];
        }
        if (empty($_GET["search"])){
            $search = "";
        } else {
            $search = $_GET["search"];
            $_SESSION["search"] = $_GET["search"];
        }
        $index = 0;
        foreach($_SESSION as $key => $sessionValue){
            if ($index >= 2) {
                echo $key . ": " . $sessionValue . "</br>";
            }
            $index ++;
        }


        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("next-page", $pageNumber + 1);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("previous-page", $pageNumber - 1);

        $this->createSelectValues($productsPerPage);
        $this->createProducts($productsPerPage, $pageNumber, $category, $search);
        $this->createPageNumbers($pageNumber);
        $this->generateRubricMenu();
        $this->generateLoginAndRegisterTemplates();

        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function createPageNumbers($currentPageNumber){
        $numberTemplates = array();
        for($i = $currentPageNumber-5; $i<$currentPageNumber; $i++){/*go back 5 pages to show the 5 previous pages in pagination*/
            if ($i >0) {/*check if the number being made is more than 0, if so, don't create anything*/
                $numberTemplates[] = $this->generatePageNumbers($i, $currentPageNumber);
            }
        }
        for($i = $currentPageNumber; $i<$currentPageNumber+6; $i++){/*go forward 5 pages, the +1 added is because the current page doesn't count*/
            $numberTemplates[] = $this->generatePageNumbers($i, $currentPageNumber);
        }
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("page-numbers", $this->HTMLBuilder->joinHTMLParameters($numberTemplates));
    }

    private function generatePageNumbers($pageNumber, $currentPageNumber){
        $numberTemplate = new HTMLParameter($this->HTMLBuilder, "content\\indexAddons\\page-number.html");
        if ($currentPageNumber == $pageNumber) { /*if the current pagenumber equals the pagenumber being made, then it should be active*/
            $numberTemplate->addTemplateParameterByString("is-active", "active");
        } else {
            $numberTemplate->addTemplateParameterByString("is-active", "");
        }
        $numberTemplate->addTemplateParameterByString("page-number", $pageNumber);
        return $numberTemplate;
    }

    public function createSelectValues($productsPerPage){
        $optionTemplates = array();
        for($i = 1; $i<6; $i++){/*creating 5 possible option to pick from which determines the amount of products per page*/
            $optionTemplates[] = $this->generateSelectValues($i*6, $productsPerPage);/* do this times 6 because everything multiplied with 6 looks good on different screen sizes and doesn't leave any open spaces*/
        }
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("options", $this->HTMLBuilder->joinHTMLParameters($optionTemplates));
    }

    private function generateSelectValues($selectValue, $productsPerPage){
        $optionTemplate = new HTMLParameter($this->HTMLBuilder, "content\\indexAddons\\option.html");
        $optionTemplate->addTemplateParameterByString("value", $selectValue);
        if ($productsPerPage == $selectValue) {/*if the current option being created already has been selected by the user, then the current option has to be selected*/
            $optionTemplate->addTemplateParameterByString("selected", "selected");
        } else {
            $optionTemplate->addTemplateParameterByString("selected", "");
        }
        return $optionTemplate;
    }

    public function createProducts($productsPerPage, $pageNumber, $category, $search){
        $pagination = new HTMLParameter($this->HTMLBuilder, "content\\indexAddons\\pagination.html");
        $productPagination = new ProductPagination($productsPerPage);
        $productPagination->setFindInTitleFilter($search);
        $productPagination->setCurrentPageNumber($pageNumber);

        if (!empty($category)) {
            $rubric = new Rubric($this->databaseHelper);
            $rubric->setId($category);
            $productPagination->setRubricFilter($rubric);
        }

        $products = $productPagination->getProducts($this->databaseHelper);
        $productTemplates = array();
        $index = 0;
        foreach($products as $product){
            $productTemplates[] = $this->generateProducts($product);
            $index++;
        }

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("products", $this->HTMLBuilder->joinHTMLParameters($productTemplates));
        if ($index == 0){
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("categories-found", "<h3>Helaas, er zijn geen producten gevonden die aan de criteria voldoen.</h3>");
        } else {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("products", $this->HTMLBuilder->joinHTMLParameters($productTemplates));
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("pagination", $pagination);
        }
    }

    /**
     * @param $product Item
     * @return HTMLParameter
     */
    private function generateProducts($product){
        $imageHelper = new ImageHelper();
        if (empty($_GET["view"])){
            $view = "grid";
        } else {
            $view = $_GET["view"];
        }
        if ($view === "grid") {
            $productTemplate = new HTMLParameter($this->HTMLBuilder, $this->productGridTemplateHTML, true);
        } else {
            $productTemplate = new HTMLParameter($this->HTMLBuilder, $this->productListTemplateHTML, true);
        }
        $productTemplate->addTemplateParameterByString("title", $product->getTitle());
        $productTemplate->addTemplateParameterByString("product-id", $product->getId());

        $images = $product->getImages();
        foreach($images as $image){
            $imagePath = $this->imageHelper->getImageLocation($image);
            if (strpos($imagePath,'thumbnails') !== false) {
                $productTemplate->addTemplateParameterByString("thumbnail-source", $imagePath);
                break;
            }
            $imagePath = $imageHelper->getImageLocation($image);
            $productTemplate->addTemplateParameterByString("image-source", $imagePath);
        }

        $auctionEndDate = $product->getAuctionEndDateTime();
        $now = new \DateTime();
        $interval = $auctionEndDate->diff($now);
        if ($product->getIsAuctionClosed()) {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", "Gesloten");
        } else {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("time-left", $interval->days." dagen ".$interval->h." uur");
        }

        $productTemplate->addTemplateParameterByString("image-source", $imagePath);

        $productTemplate->addTemplateParameterByString("price", number_format($product->getStartPrice(), 2, '.',''));
        return $productTemplate;
    }

    /**
     * @return Rubric
     */
    public function getRootRubricWithChildrenLoaded() {
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "{call sp_selectRubric }");
        if($statement === false) {
            echo "Error in executing statement 3.\n";
            die( print_r( sqlsrv_errors(), true));
        } else {
            /** @var $rubrics Rubric[] */
            $rubrics = array();
            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $rubric = new Rubric($this->databaseHelper, $row["rubricId"]);
                $rubric->setIsLoaded(true);
                $rubric->setName($row["rubricName"]);
                $rubric->setParentRubricId($row["parentRubric"]);
                $rubric->setAmountOfProductsRelated($row["productCountIncludingChildren"]);
                $rubrics[$rubric->getId()] = $rubric;
                if (array_key_exists($row["parentRubric"], $rubrics)) {
                    $rubrics[$row["parentRubric"]]->addChild($rubric);
                }
            }

            return $rubrics[1]; //return root
        }
    }

    private function generateRubricMenu()
    {
        $isUpToDate = 0;
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "{call sp_isCachedRubricUpToDate(?) }", array(array(&$isUpToDate, SQLSRV_PARAM_INOUT)));
        if ($statement === false) {
            echo "Error in executing statement 3.\n";
            die(print_r(sqlsrv_errors(), true));
        }
        if ($isUpToDate === 0) {
            $rootRubric = $this->getRootRubricWithChildrenLoaded();
            /**
             * @var $mobileRubricTemplates HTMLParameter[]
             * @var $desktopRubricTemplates HTMLParameter[]
             */
            $mobileRubricTemplates = $desktopRubricTemplates = array();

            foreach ($rootRubric->getChildren() as $childRubric) {
                $mobileRubricTemplates[] = $this->generateMobileRubricChildren($childRubric);
                $desktopRubricTemplates[] = $this->generateDesktopRubricChildren($childRubric);
            }

            $mobileRubricTemplate = $this->HTMLBuilder->joinHTMLParameters($mobileRubricTemplates);
            $this->HTMLBuilder->cacheHTML("mobile-rubric-template.html", $mobileRubricTemplate);
            $desktopRubricTemplate = $this->HTMLBuilder->joinHTMLParameters($desktopRubricTemplates);
            $this->HTMLBuilder->cacheHTML("desktop-rubric-template.html", $desktopRubricTemplate);
        } else {
            $mobileRubricTemplate = (new HTMLParameter($this->HTMLBuilder, "cache\\mobile-rubric-template.html"))->parseAndGetHTML();
            $desktopRubricTemplate = (new HTMLParameter($this->HTMLBuilder, "cache\\desktop-rubric-template.html"))->parseAndGetHTML();
        }

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("mobile-category", $mobileRubricTemplate);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("desktop-category", $desktopRubricTemplate);
    }

    /**
     * @param $rubric Rubric
     * @return HTMLParameter
     */
    private function generateMobileRubricChildren($rubric) {
        $toRemove = array(" ", ",", "'", "&");/* an array containing the characters that should be removed for the target of the buttons*/
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\rubric\\mobile-category.html");
        $rubricTemplate->addTemplateParameterByString("name", $rubric->getName());
        $rubricTemplate->addTemplateParameterByString("id", $rubric->getId());
        $rubricTemplate->addTemplateParameterByString("target-main-category", str_replace($toRemove, "", $rubric->getId()."-".$rubric->getName()));/*removing the special characters from the target using the earlier declared array*/
        $rubricTemplate->addTemplateParameterByString("amountOfProductsRelated", $rubric->getAmountOfProductsRelated());

        $childRubrics = $rubric->getChildren();
        $childRubricTemplates = array();
        if($childRubrics !== null) {
            foreach($childRubrics as $childRubric) {
                $childRubricTemplates[] = $this->generateMobileRubricChildren($childRubric);
            }
        }

        $rubricTemplate->addTemplateParameterByString("child-categories", $this->HTMLBuilder->joinHTMLParameters($childRubricTemplates));
        return $rubricTemplate;
    }

    /**
     * @param $rubric Rubric
     * @return HTMLParameter
     */
    private function generateDesktopRubricChildren($rubric) {
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\rubric\\desktop-category.html");
        $rubricChildCategories = new HTMLParameter($this->HTMLBuilder, "content\\rubric\\desktop-child-categories.html");
        $rubricTemplate->addTemplateParameterByString("name", $rubric->getName());
        $rubricTemplate->addTemplateParameterByString("id", $rubric->getId());
        $rubricTemplate->addTemplateParameterByString("amountOfProductsRelated", $rubric->getAmountOfProductsRelated());

        $childRubrics = $rubric->getChildren();
        $childRubricTemplates = array();
        if($childRubrics !== null) {
            foreach($childRubrics as $childRubric) {
                $childRubricTemplates[] = $this->generateDesktopRubricChildren($childRubric);
            }
        }
        if (!empty($childRubricTemplates)) {
            $rubricTemplate->addTemplateParameterByParameter("child-category", $rubricChildCategories);
            $rubricTemplate->addTemplateParameterByString("is-child", "dropdown-submenu dropdown-menu-right");
        }
        $rubricTemplate->addTemplateParameterByString("child-categories-desktop", $this->HTMLBuilder->joinHTMLParameters($childRubricTemplates));

        return $rubricTemplate;
    }
}

$page = new Index();
echo $page->createHTML();