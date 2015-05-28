<?php
namespace src\classes\Pages;
use src\classes\HTMLBuilder;
use src\classes\HTMLBuilder\HTMLParameter;
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

        $HTMLCategoryDesktop = new HTMLParameter($this->HTMLBuilder, "content\\desktop-category.html");
        $HTMLCategoryMobile = new HTMLParameter($this->HTMLBuilder, "content\\mobile-category.html");

        $HTMLCategoriesDes = array(); /*template for desktop categories*/
        $HTMLCategoriesMob = array(); /*template for mobile categories*/
        $categoryname = array(); /*array for the main category names*/

        $databaseHelper = new \src\classes\DatabaseHelper();
        $rubric = new \src\classes\Models\Rubric($databaseHelper, 1);
        $mainCategories = $rubric->getChildren();

        foreach($mainCategories as $category){
            $categoryname[] = $category->getName();
        }

        /** @var $HTMLCategoriesDes HTMLParameter[] */
        for($i = 0; $i < sizeof($categoryname); $i++) { /*adding the main category names to the template for desktop*/
            $HTMLCategoriesDes[$i] = new HTMLParameter($this->HTMLBuilder, "content\\desktop-category.html");
            $HTMLCategoriesDes[$i]->addTemplateParameterByString("name", $categoryname[$i]);
        }

        $toRemove = array(" ", ",", "'");/* an array containing the characters that should be removed for the target of the buttons*/

        /** @var $HTMLCategoriesMob HTMLParameter[] */
        for($i = 0; $i < sizeof($categoryname); $i++) { /*adding the main category names to the template for mobile*/
            $HTMLCategoriesMob[$i] = new HTMLParameter($this->HTMLBuilder, "content\\mobile-category.html");
            $HTMLCategoriesMob[$i]->addTemplateParameterByString("name", $categoryname[$i]);
            $HTMLCategoriesMob[$i]->addTemplateParameterByString("target-main-category", str_replace($toRemove, "",$categoryname[$i]));/*removing the special characters from the target using the earlier declared array*/
            $HTMLCategoriesMob[$i]->addTemplateParameterByString("name", $categoryname[$i]);
            foreach($mainCategories as $maincategory){
                $a = $maincategory->getChildren();
                foreach($a as $b) {
                    $HTMLCategoriesMob[$i]->addTemplateParameterByString("child-categories", $b->getName());
                }
            }
        }

        /*inserting the names into the template*/
        $HTMLCategoryDesktop->addTemplateParameterByString("name", $this->HTMLBuilder->joinHTMLParameters($HTMLCategoriesDes));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("desktop-category", $HTMLCategoryDesktop);
        $HTMLCategoryMobile->addTemplateParameterByString("name", $this->HTMLBuilder->joinHTMLParameters($HTMLCategoriesMob));
        $this->HTMLBuilder->mainHTMLParameter->addTemplateParameterByParameter("mobile-category", $HTMLCategoryMobile);

        echo $this->HTMLBuilder->getHTML();
    }
}