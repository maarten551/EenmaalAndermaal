<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\File;
use src\classes\Models\Item;
use \src\classes\ImageHelper;
use src\classes\Models\Question;
use src\classes\Models\Rubric;
use src\classes\Page;
use src\classes\ProductPagination;
use src\classes\SpeedTester;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class Index extends Page {
    private $imageHelper;
    private $productTemplateHTML;
    private $mobileMenuTemplateHTML;
    private $desktopMenuTemplateHTML;
    private $desktopMenuChildrenTemplateHTML;

    public function __construct() {
        parent::__construct("template.html");

        $this->imageHelper = new ImageHelper();

        /* Save all HTML files into memory, otherwise every time a new template is loaded, */
        $this->productTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("product\\product-item.html");
        $this->mobileMenuTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("content\\rubric\\mobile-category.html");
        $this->desktopMenuTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("content\\rubric\\desktop-category.html");
        $this->desktopMenuChildrenTemplateHTML = $this->HTMLBuilder->loadHTMLFromFile("content\\rubric\\desktop-child-categories.html");
    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        $this->createProducts();
        $this->generateRubricMenu();
        $this->generateLoginAndRegisterTemplates();

        echo $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function createProducts(){//TODO abilty to give a variable to this function wich determins the amount of products per page
        $productPagination = new ProductPagination(9);

        $products = $productPagination->getProducts($this->databaseHelper);

        $productTemplates = array();
        foreach($products as $product){
            $productTemplates[] = $this->generateProducts($product);
        }

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("products", $this->HTMLBuilder->joinHTMLParameters($productTemplates));
    }

    /**
     * @param $product Item
     * @return HTMLParameter
     */
    private function generateProducts($product){
        $productTemplate = new HTMLParameter($this->HTMLBuilder, $this->productTemplateHTML, true);
        $productTemplate->addTemplateParameterByString("title", $product->getTitle());
        $productTemplate->addTemplateParameterByString("product-id", $product->getId());

        $images = $product->getImages();
        $imagePath = "";
        foreach($images as $image){
            $imagePath = $this->imageHelper->getImageLocation($image);
            if (strpos($imagePath,'pics') !== false) {
                $productTemplate->addTemplateParameterByString("thumbnail-source", $imagePath);
                break;
            }
        }

        $productTemplate->addTemplateParameterByString("thumbnail-source", $imagePath);
        $productTemplate->addTemplateParameterByString("price", floatval($product->getStartPrice()));

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

    private function generateRubricMenu() {
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

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("mobile-category", $this->HTMLBuilder->joinHTMLParameters($mobileRubricTemplates));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("desktop-category", $this->HTMLBuilder->joinHTMLParameters($desktopRubricTemplates));
    }

    /**
     * @param $rubric Rubric
     * @return HTMLParameter
     */
    private function generateMobileRubricChildren($rubric) {
        $toRemove = array(" ", ",", "'", "&");/* an array containing the characters that should be removed for the target of the buttons*/
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, $this->mobileMenuTemplateHTML, true);
        $rubricTemplate->addTemplateParameterByString("name", $rubric->getName());
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
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, $this->desktopMenuTemplateHTML, true);
        $rubricTemplate->addTemplateParameterByString("name", $rubric->getName());
        $rubricTemplate->addTemplateParameterByString("amountOfProductsRelated", $rubric->getAmountOfProductsRelated());

        $childRubrics = $rubric->getChildren();
        $childRubricTemplates = array();
        if($childRubrics !== null) {
            foreach($childRubrics as $childRubric) {
                $childRubricTemplates[] = $this->generateDesktopRubricChildren($childRubric);
            }
        }
        if (!empty($childRubricTemplates)) {
            $rubricTemplate->addTemplateParameterByString("child-category", $this->desktopMenuChildrenTemplateHTML);
            $rubricTemplate->addTemplateParameterByString("child-categories-desktop", $this->HTMLBuilder->joinHTMLParameters($childRubricTemplates));
            $rubricTemplate->addTemplateParameterByString("is-child", "dropdown-submenu dropdown-menu-right");
        }

        return $rubricTemplate;
    }
}

$page = new Index();
echo $page->createHTML();