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

    public function __construct() {
        parent::__construct("template.html");

        $this->imageHelper = new ImageHelper();

        if (!isset($_SESSION["productsPerPage"])){
            $_SESSION["productsPerPage"] = 6;
        }
        if (!isset($_SESSION["view"])) {
            $_SESSION["view"] = "grid";
        }
        if (!isset($_SESSION["pageNumber"])) {
            $_SESSION["pageNumber"] = 1;
        }
        if (!isset($_SESSION["search"])) {
            $_SESSION["search"] = "";
        }
        if (!isset($_SESSION["category"])) {
            $_SESSION["category"] = 1;
        }


        if (!empty($_GET["productsPerPage"])) {
            $_SESSION["productsPerPage"] = $_GET["productsPerPage"];
        }
        if (!empty($_GET["view"])){
            $_SESSION["view"] = $_GET["view"];
        }
        if (!empty($_GET["pageNumber"])){
            if($_GET["pageNumber"] >=0){
                $_SESSION["pageNumber"] = $_GET["pageNumber"];
            } else{
                $_SESSION["pageNumber"] = 0;
            }
        }
        if (!empty($_GET["search"])){
            $_SESSION["pageNumber"] = 1;
            $_SESSION["search"] = $_GET["search"];
        }
        if (!empty($_GET["category"])){
            $_SESSION["search"] = "";
            $_SESSION["pageNumber"] = 1;
            $_SESSION["category"] = $_GET["category"];
        }


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

        if ($_SESSION["search"] != "") {
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("searched-for", "Resultaten voor: " . '"'.$_SESSION["search"].'"');
        }
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("next-page", $_SESSION["pageNumber"] + 1);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("previous-page", $_SESSION["pageNumber"] - 1);

        $this->createSelectValues();
        $this->generateRubricMenu();
        $this->createProducts();
        $this->createPageNumbers();
        $this->generateLoginAndRegisterTemplates();

        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }
    public function createPageNumbers(){
        $numberTemplates = array();
        $currentPageNumber = $_SESSION["pageNumber"];
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

    public function createSelectValues(){
        $productsPerPage = $_SESSION["productsPerPage"];
        $optionTemplates = array();
        for($i = 1; $i<6; $i++){/*creating 5 possible option to pick from which determines the amount of products per page*/
            $optionTemplates[] = $this->generateSelectValues($i*6, $productsPerPage);/* do this times 6 because everything multiplied with 6 looks good on different screen sizes and doesn't leave any open spaces*/
        }
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("options", $this->HTMLBuilder->joinHTMLParameters($optionTemplates));
    }

    private function generateSelectValues($selectValue){
        $optionTemplate = new HTMLParameter($this->HTMLBuilder, "content\\indexAddons\\option.html");
        $optionTemplate->addTemplateParameterByString("value", $selectValue);
        if ($selectValue == $_SESSION["productsPerPage"]) {/*if the current option being created already has been selected by the user, then the current option has to be selected*/
            $optionTemplate->addTemplateParameterByString("selected", "selected");
        } else {
            $optionTemplate->addTemplateParameterByString("selected", "");
        }
        return $optionTemplate;
    }

    public function createProducts(){
        $pagination = new HTMLParameter($this->HTMLBuilder, "content\\indexAddons\\pagination.html");
        $productPagination = new ProductPagination($_SESSION["productsPerPage"]);
        $productPagination->setFindInTitleFilter($_SESSION["search"]);
        $productPagination->setCurrentPageNumber($_SESSION["pageNumber"]);

        if (!empty($_SESSION["category"])) {
            $rubric = new Rubric($this->databaseHelper);
            $rubric->setId($_SESSION["category"]);
            $productPagination->setRubricFilter($rubric);
            $name = $rubric->getName();
            if ($rubric->getId() == 1){
                $name = "Alle";
            }
            if ($_SESSION["search"] == "") {
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("category-in", "Categorie: ".$name);
            } else {
                $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("category-in", "in de categorie: ".$name);
            }
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
        if ($_SESSION["view"] === "grid") {
            $productTemplate = new HTMLParameter($this->HTMLBuilder, "product\\product-item-grid.html");
        } else {
            $productTemplate = new HTMLParameter($this->HTMLBuilder, $this->productListTemplateHTML, true);
        }
        $productTemplate->addTemplateParameterByString("title", $product->getTitle());
        $productTemplate->addTemplateParameterByString("product-id", $product->getId());

        $images = $product->getImages();
        $imagePath = "";
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
        if ($interval->days == 1){
            $productTemplate->addTemplateParameterByString("time-left","nog ".$interval->days." dag en ".$interval->h." uur.");
        } else {
            $productTemplate->addTemplateParameterByString("time-left","nog ".$interval->days." dagen en ".$interval->h." uur.");
        }

        $productTemplate->addTemplateParameterByString("image-source", ($imagePath !== "") ? $imagePath : ImageHelper::$NO_FILE_FOUND_LOCATION);
        $highestPrice = $product->getStartPrice();
        if(count($product->getBids()) >= 1) {
            $highestPrice = $product->getBids()[0]->getAmount();
        }
        $productTemplate->addTemplateParameterByString("price", number_format($highestPrice, 2, '.',''));
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
            if(count($rubrics) >= 1) {
                return $rubrics[1]; //return root
            } else {
                return null;
            }
        }
    }

    private function generateRubricMenu()
    {
        $isUpToDate = 0;
        $mobileRubricTemplate = "";
        $desktopRubricTemplate = "";
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "{call sp_isCachedRubricUpToDate(?) }", array(array(&$isUpToDate, SQLSRV_PARAM_INOUT)));
        if ($statement === false) {
            echo "Error in executing statement 3.\n";
            die(print_r(sqlsrv_errors(), true));
        }

        if ($isUpToDate === 0 || true) {
            $rootRubric = $this->getRootRubricWithChildrenLoaded();
            if($rootRubric !== null) {
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
            }
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

        $childRubrics = $rubric->getChildren(true);
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

        $childRubrics = $rubric->getChildren(true);
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