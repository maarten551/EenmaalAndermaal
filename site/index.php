<?php
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\File;
use src\classes\Models\Item;
use src\classes\Models\Question;
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
        $imageHelper = new \src\classes\ImageHelper();
        $fileTest = new File($this->databaseHelper, "dt_1_110302450194.jpg", 1);

        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");
        $content->addTemplateParameterByString("image-link", $imageHelper->getImageLocation($fileTest));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);
        $registerModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\register-modal.html");
        $loginModal = new HTMLParameter($this->HTMLBuilder, "content\\modal\\inloggen-modal.html");
        $question = new HTMLParameter($this->HTMLBuilder, "content\\question.html");

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("inloggen-modal", $loginModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("register-modal", $registerModal);
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("questions", $question);

        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("max-birthdate", date('d-m-Y'));

        $item = new Item($this->databaseHelper, 2917);
        foreach ($item->getImages() as $image) {
            echo "<img src='".$imageHelper->getImageLocation($image)."'/>";
        }

        $this->generateQuestionTemplate();
        return $this->HTMLBuilder->getHTML();
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function getQuestions(){
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), "select questionText from question");
        if($statement === false) {
            echo "Error in executing statement.\n";
            die( print_r( sqlsrv_errors(), true));
        } else {
            $questions = array();
            while ($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $question = new Question($this->databaseHelper);
                $question->mergeQueryData($row);
                $questions[] = $question->getQuestionText();
            }
            return $questions;
        }
    }

    private function generateQuestionTemplate(){
        $questions = $this->getQuestions();
        foreach($questions as $question){
            $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("question-name", $question);
        }
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
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\rubric\\mobile-category.html");
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
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\rubric\\desktop-category.html");
        $rubricChildCategories = new HTMLParameter($this->HTMLBuilder, "content\\rubric\\desktop-child-categories.html");
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