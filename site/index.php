<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Rubric;
use src\classes\Page;

require_once "src/libraries/password.php"; //For password hashing functionality for PHP < 5.5, server is 5.4.35, source: https://github.com/ircmaxell/password_compat

function __autoload($class_name) { //PHP will use this function if a class file hasn't been read yet.
	require $class_name . '.php';
}

session_start();
date_default_timezone_set("Europe/Amsterdam");

class Index extends Page {
    public function __construct() {
        parent::__construct("template.html");
    }

    public function createHTML()
    {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        $this->generateRubricMenu();
        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function getRootRubricWithChildrenLoaded() {
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "{call sp_selectRubric }");
        if($statement === false) {
            echo "Error in executing statement 3.\n";
            die( print_r( sqlsrv_errors(), true));
        } else {
            $rubrics = array();
            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $rubric = new Rubric($this->databaseHelper);
                $rubric->mergeQueryData($row);
                $rubric->setAmountOfProductsRelated($row["productCountIncludingChildren"]);
                $rubrics[$rubric->getId()] = $rubric;
                if (array_key_exists($row["childOfRubric"], $rubrics)) {
                    $rubrics[$row["childOfRubric"]]->addChild($rubric);
                }
            }

            return $rubrics[1]; //return root
        }
    }

    private function generateRubricMenu() {
        $HTMLCategoriesDes = array(); /*template for desktop categories*/

        $rubric = $this->getRootRubricWithChildrenLoaded();
        $mainCategories = $rubric->getChildren();

        /** @var $HTMLCategoriesDes HTMLParameter[] */
        for($i = 0; $i < count($mainCategories); $i++) { /*adding the main category names to the template for desktop*/
            $HTMLCategoriesDes[$i] = new HTMLParameter($this->HTMLBuilder, "content\\desktop-category.html");
            $HTMLCategoriesDes[$i]->addTemplateParameterByString("name", $mainCategories[$i]->getName());
        }

        $rubrics = $this->generateMobileRubricChildren($rubric);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("desktop-category", $this->HTMLBuilder->joinHTMLParameters($HTMLCategoriesDes));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("mobile-category", $rubrics);
    }

    /**
     * @param $rubric Rubric
     * @return HTMLParameter
     */
    private function generateMobileRubricChildren($rubric) {
        $toRemove = array(" ", ",", "'");/* an array containing the characters that should be removed for the target of the buttons*/
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\mobile-category.html");
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
}

$page = new Index();
echo $page->createHTML();