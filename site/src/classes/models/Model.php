<?php
namespace src\classes\models;

use src\classes\DatabaseHelper;

abstract class Model {
    /**
     * @var String
     */
    protected $tableName;
    /**
     * @var String
     */
    protected $primaryKeyName;
    /**
     * @var array
     */
    protected $databaseFields = array(
        "required" => array(),
        "optional" => array()
    );
    /**
     * @var bool
     */
    private $isLoaded;
    /**
     * @var bool
     */
    private $isDirty;
    /**
     * @var DatabaseHelper
     */
    private $datebaseHelper;

    public function __construct(DatabaseHelper $databaseHelper) {
        $this->datebaseHelper = $databaseHelper;
        $this->isDirty = false;
        $this->isLoaded = false;
    }

    protected function load($primaryKeyValue) {

    }

    protected function get($fieldName, $ignoreIsLoaded = false) {
        if(property_exists($this, $fieldName)) {
            echo "property exists: ".$this->$fieldName;
        } else {
            echo "Property doesn't exists";
        }

        if($fieldName !== $this->primaryKeyName && ($this->isFieldInDatabase($fieldName) || $ignoreIsLoaded === false)) {
            $this->load("test"); //TODO fix
        }
    }

    /**
     * @param $fieldName
     * @return bool
     */
    private function isFieldInDatabase($fieldName) {
        foreach ($this->databaseFields as $type) {
            foreach ($type as $databaseField) {
                if($databaseField === $fieldName) {
                    return true;
                }
            }
        }

        return false;
    }
}