<?php
namespace src\classes\Pages;
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Rubric;

class Home extends AbstractPage {
    private $HTMLBuilder;

    public function __construct($parameters) {
        parent::__construct($parameters);
        $this->HTMLBuilder = new HTMLBuilder("template.html");
        $this->createHTML();
    }

    protected function createHTML() {
        $content = new HTMLParameter($this->HTMLBuilder, "content\\content-homepage.html");
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("content", $content);

        $this->generateRubricMenu();

        echo $this->HTMLBuilder->getHTML();
    }

    /**
     * @param $rubric Rubric
     * @return HTMLParameter
     */
    private function generateRubricChildren($rubric) {
        $childRubrics = $rubric->getChildren();
        $childRubricTemplates = array();
        foreach($childRubrics as $childRubric) {
            $childRubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\mobile-category.html");
            $childRubricTemplate->addTemplateParameterByString("name", $childRubric->getName());
            $childRubricTemplates[] = $childRubricTemplate;
        }

        return null;
    }

    private function generateRubricMenu() {
        $HTMLCategoriesDes = array(); /*template for desktop categories*/
        $HTMLCategoriesMob = array(); /*template for mobile categories*/

        $databaseHelper = new DatabaseHelper();
        $rubric = new Rubric($databaseHelper, 1);
        $mainCategories = $rubric->getChildren();

        /** @var $HTMLCategoriesDes HTMLParameter[] */
        for($i = 0; $i < count($mainCategories); $i++) { /*adding the main category names to the template for desktop*/
            $HTMLCategoriesDes[$i] = new HTMLParameter($this->HTMLBuilder, "content\\desktop-category.html");
            $HTMLCategoriesDes[$i]->addTemplateParameterByString("name", $mainCategories[$i]->getName());
        }

        $toRemove = array(" ", ",", "'");/* an array containing the characters that should be removed for the target of the buttons*/

        $this->generateRubricChildren($rubric);
        /** @var $HTMLCategoriesMob HTMLParameter[] */
        for($i = 0; $i < count($mainCategories); $i++) { /*adding the main category names to the template for mobile*/
            $HTMLCategoriesMob[$i] = new HTMLParameter($this->HTMLBuilder, "content\\mobile-category.html");
            $HTMLCategoriesMob[$i]->addTemplateParameterByString("name", $mainCategories[$i]->getName());
            $HTMLCategoriesMob[$i]->addTemplateParameterByString("target-main-category", str_replace($toRemove, "", $mainCategories[$i]->getName()));/*removing the special characters from the target using the earlier declared array*/



            $HTMLCategoriesMob[$i]->addTemplateParameterByString("child-categories", $this->HTMLBuilder->joinHTMLParameters($childRubricTemplates));
        }

        /*inserting the names into the template*/
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("desktop-category", $this->HTMLBuilder->joinHTMLParameters($HTMLCategoriesDes));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByString("mobile-category", $this->HTMLBuilder->joinHTMLParameters($HTMLCategoriesMob));
    }
}