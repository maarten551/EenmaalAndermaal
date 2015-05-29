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

    /**
     * @param $rubric Rubric
     * @return HTMLParameter
     */
    private function generateDesktopRubricChildren($rubric) {
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\desktop-category.html");
        $rubricChildCategories = new HTMLParameter($this->HTMLBuilder, "content\\desktop-child-categories.html");
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
            $rubricTemplate->addTemplateParameterByParameter("child-category", $rubricChildCategories);
            $rubricTemplate->addTemplateParameterByString("is-child", "dropdown-submenu dropdown-menu-right");
        }
            $rubricTemplate->addTemplateParameterByString("child-categories-desktop", $this->HTMLBuilder->joinHTMLParameters($childRubricTemplates));

        return $rubricTemplate;
    }
}

$page = new Index();
echo $page->createHTML();