<?php
namespace src\classes\HTMLBuilder;
use src\classes\HTMLBuilder;

class HTMLParameter {
    /**
     * @var HTMLBuilder
     */
    private $HTMLBuilder;
    /**
     * @var HTMLParameter[]
     */
    private $HTMLParameters = array();
    private $templateHTML;

    public function __construct(HTMLBuilder $HTMLBuilder, $templateFileLocation = "") {
        $this->HTMLBuilder = $HTMLBuilder;
        if($templateFileLocation !== "") {
            $this->templateHTML = $this->HTMLBuilder->loadHTMLFromFile($templateFileLocation);
        }
    }

    private function parseTemplateVariables() {
        $parsedHTML = $this->templateHTML;
        foreach($this->HTMLParameters as $variableName => $HTMLParameter) {
            $inTemplateName = "#$($variableName)$#";
            $parsedHTML = str_replace($inTemplateName, $HTMLParameter->parseAndGetHTML(), $parsedHTML);
        }

        return $parsedHTML;
    }

    public function addTemplateParameterByParameter($templateVariableName, HTMLParameter $HTMLParameter) {
        $this->HTMLParameters[$templateVariableName] = $HTMLParameter;
    }

    public function addTemplateParameterByString($templateVariableName,  $HTML) {
        $parameter = new HTMLParameter($this->HTMLBuilder);
        $parameter->setHTML($HTML);
        $this->HTMLParameters[$templateVariableName] = $parameter;
    }

    public function setHTML($HTML) {
        $this->templateHTML = $HTML;
    }

    public function parseAndGetHTML() {
        return $this->parseTemplateVariables();
    }
}