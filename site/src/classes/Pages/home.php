<?php
namespace src\classes\Pages;
use src\classes\DatabaseHelper;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Models\Rubric;

class Home extends AbstractPage {
    private $HTMLBuilder;
    private $rubricCounter = 0;

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
    private function generateMobileRubricChildren($rubric) {
        $toRemove = array(" ", ",", "'");/* an array containing the characters that should be removed for the target of the buttons*/
        $rubricTemplate = new HTMLParameter($this->HTMLBuilder, "content\\mobile-category.html");
        $rubricTemplate->addTemplateParameterByString("name", $rubric->getName());
        $rubricTemplate->addTemplateParameterByString("target-main-category", str_replace($toRemove, "", $rubric->getId()."-".$rubric->getName()));/*removing the special characters from the target using the earlier declared array*/

        $childRubrics = $rubric->getChildren();
        $childRubricTemplates = array();
        if($childRubrics !== null) {
            foreach($childRubrics as $childRubric) {
                if($this->rubricCounter <= 1500) {
                    $this->rubricCounter++;
                    $childRubricTemplates[] = $this->generateMobileRubricChildren($childRubric);
                }
            }
        }

        $rubricTemplate->addTemplateParameterByString("child-categories", $this->HTMLBuilder->joinHTMLParameters($childRubricTemplates));
        return $rubricTemplate;
    }

    private function generateRubricMenu() {
        $HTMLCategoriesDes = array(); /*template for desktop categories*/

        $databaseHelper = new DatabaseHelper();
        $rubric = new Rubric($databaseHelper, 1);
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

}