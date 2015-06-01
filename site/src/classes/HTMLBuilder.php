<?php
namespace src\classes;
use src\classes\HTMLBuilder\HTMLParameter;
use src\classes\Messages\AbstractMessage;
use src\classes\Messages\Alert;

/**
 * Created by PhpStorm.
 * User: Maarten Bobbeldijk
 * Date: 17/2/2015
 * Time: 11:49 AM
 */

class HTMLBuilder {
    /**
     * Where are the template files located
     */
    const BASE_LOCATION = "/src/html/";
    /** @var AbstractMessage[] */
    private $messages = array();
    /**
     * @var HTMLParameter
     */
    public $mainHTMLParameter;


    /**
     * @param $fileName
     */
    public function __construct($fileName) {
        $this->mainHTMLParameter = new HTMLParameter($this, $fileName);
        $this->addMessage(new Alert($this));
        $this->addMessage(new Alert($this));
        $this->addMessage(new Alert($this));
    }

    /**
     * @param $fileName
     * @return String
     */
    public function loadHTMLFromFile($fileName) {
        $html = "";
        $templateFileLocation = getcwd().self::BASE_LOCATION.$fileName;
        if($this->fileExists($templateFileLocation)) {
            $HTMLFile = fopen($templateFileLocation, "r");
            $html = fread($HTMLFile, filesize($templateFileLocation));
            fclose($HTMLFile);
        }

        return $html;
    }


    /**
     * @param $HTMLParameters HTMLParameter[]
     * @return string
     */
    public function joinHTMLParameters($HTMLParameters) {
        $joinedHTML = "";
        foreach($HTMLParameters as $HTMLParameter) {
            $joinedHTML .= $HTMLParameter->parseAndGetHTML();
        }

        return $joinedHTML;
    }

    /**
     * @param $fileLocation String
     * @return boolean
     */
    private function fileExists($fileLocation) {
        if(file_exists($fileLocation)) {
            return true;
        } else {
            echo "File doesn't exists: ".$fileLocation;
            return false;
        }

        return false;
    }


    /**
     * @param $html
     * @return mixed
     * Removes every unused HTMLBuilder variable inside the $html.
     * For example:
     *      #$(login-date)$#
     */
    private function cleanUpNotUsedVariables($html) {
        return preg_replace("/#\\$\\(.*\\)\\$#/", "", $html);
    }

    private function buildMessagesHTML() {
        $messageContainerTemplate = new HTMLParameter($this, "messages\\message-container.html");
        if(count($this->messages) > 0) {
            $html = "";
            foreach($this->messages as $message) {
                $html .= $message->toHTMLParameter()->parseAndGetHTML();
            }
            $messageContainerTemplate->addTemplateParameterByString("messages", $html);
            $this->mainHTMLParameter->addTemplateParameterByParameter("message-container", $messageContainerTemplate);
        }
    }

    /**
     * @return mixed|string
     */
    public function getHTML() {
        $this->buildMessagesHTML();
        $html = $this->mainHTMLParameter->parseAndGetHTML();

        return $this->cleanUpNotUsedVariables($html);
    }

    /**
     * @param $message AbstractMessage
     */
    public function addMessage($message) {
        $this->messages[] = $message;
    }

    /**
     * @param $fileLocation
     * @param $HTML
     * Caches the HTML is given fileLocation
     */
    public function cacheHTML($fileLocation, $HTML) {
        $fileLocation = getcwd().self::BASE_LOCATION."cache/".$fileLocation;
        if(file_exists($fileLocation)) {
            unlink($fileLocation);
        }

        $fileHandler = fopen($fileLocation, "wr+");
        fwrite($fileHandler, $HTML);
        fclose($fileHandler);
    }
}