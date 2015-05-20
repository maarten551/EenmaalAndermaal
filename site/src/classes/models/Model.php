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
    protected $hasIdentity;
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
    private $databaseHelper;

    public function __construct(DatabaseHelper $databaseHelper) {
        $this->databaseHelper = $databaseHelper;
        $this->isDirty = false;
        $this->isLoaded = false;
    }

    protected function load($primaryKeyValue) {

    }

    public function save() {
        if($this->isLoaded === false) {
            $primaryKeyValue = $this->get($this->primaryKeyName);
            if($primaryKeyValue === null && $this->hasIdentity === true) {
                //TODO:Check if all the required fields aren't empty
                $currentObjectDataFields = $this->getDatabaseFieldsWithValues();
                $insertQuery = "INSERT INTO $this->tableName (".implode(", ", array_keys($currentObjectDataFields)).") VALUES (".implode(", ", $currentObjectDataFields)."); SELECT SCOPE_IDENTITY()";

                $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $insertQuery, array(
                    implode(", ", array_keys($currentObjectDataFields)),
                    implode(", ", $currentObjectDataFields)
                ));

                if(!sqlsrv_execute($statement)) {
                    die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to insert
                }

                $primaryKeyName = $this->primaryKeyName;
                $this->$primaryKeyName = $this->databaseHelper->getLastInsertedId($statement);



                var_dump(implode(", ", $currentObjectDataFields));
                var_dump(implode(", ", array_keys($currentObjectDataFields)));
                var_dump($this);
            } else if ($this->hasIdentity === false) {
                //TODO: save while primary key is known
            }
        } else {
            //TODO: Update code
        }
    }

    /**
     * @param $fieldName
     * @param bool $ignoreIsLoaded
     * @return mixed
     */
    protected function get($fieldName, $ignoreIsLoaded = false) {
        if(property_exists($this, $fieldName)) {
            echo "Property '$fieldName' exists: ".$this->$fieldName."<br />";
            return $this->$fieldName;
        } else {
            die("Property '$fieldName' doesn't exists in class '".get_class($this)."'");
            return null;
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

    private function getDatabaseFieldsWithValues() {
        $databaseFieldsWithValues = array();
        foreach ($this->databaseFields as $type) {
            foreach ($type as $databaseField => $databaseType) {
                /*$fieldValue = $this->databaseHelper->prepareString($this->get($databaseField));
                $databaseFieldsWithValues[$databaseField] = ($databaseType === "quote") ? "'".$fieldValue."'" : $fieldValue;*/
                $databaseFieldsWithValues[$databaseField] = $this->databaseHelper->prepareString($this->get($databaseField));
            } //TODO: test this.
        }

        return $databaseFieldsWithValues; //Removes the last ', '
    }
}