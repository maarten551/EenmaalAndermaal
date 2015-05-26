<?php
namespace src\classes\Models;

use src\classes\DatabaseHelper;

abstract class Model
{
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
    protected $databaseHelper;

    public function __construct(DatabaseHelper $databaseHelper)
    {
        $this->databaseHelper = $databaseHelper;
        $this->isDirty = false;
        $this->isLoaded = false;
    }

    protected function load($primaryKeyValue) {
        if ($primaryKeyValue !== null) {
            $databaseFields = $this->getDatabaseFieldsWithValues(); //We need to know the keys for selecting the columns
            $selectQuery = "SELECT " . implode(', ', array_keys($databaseFields)) . "
                FROM [$this->tableName]
                WHERE $this->primaryKeyName = ?";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $selectQuery, array(
                &$primaryKeyValue
            ));

            if (!sqlsrv_execute($statement)) {
                die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to select
            }

            if (sqlsrv_has_rows($statement)) {
                $this->setPrimaryKeyField($primaryKeyValue);
                $this->mergeQueryData(sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC));
                $this->isLoaded = true;
            }
        }
    }

    public function save()
    {
        if ($this->isDirty === true) {
            $currentObjectDataFields = $this->getDatabaseFieldsWithValues();
            if ($this->areFieldValuesValid($currentObjectDataFields)) {
                $primaryKeyValue = $this->getPrimaryKeyField();
                if ($this->isLoaded === false) {
                    if ($primaryKeyValue === null && $this->hasIdentity === true) {//test
                        $insertQuery = "INSERT INTO $this->tableName "
                            . "(" . implode(", ", array_keys($currentObjectDataFields)) . ") "
                            . "VALUES (" . implode(", ", $currentObjectDataFields) . ")"
                            . "SELECT SCOPE_IDENTITY()"; //It seems you can't pass column names as a parameter

                        $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $insertQuery);

                        if (!sqlsrv_execute($statement)) {
                            die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to insert
                        }

                        $this->setPrimaryKeyField($this->databaseHelper->getLastInsertedId($statement));
                        $this->isDirty = false;
                        $this->isLoaded = true;
                    } else if ($this->hasIdentity === false) {
                        //TODO: save while primary key is known and is not an identity
                    }
                } else {
                    $fieldUpdateInQuery = $this->prepareDatabaseFieldsForUpdate($currentObjectDataFields);
                    $updateQuery = "UPDATE [$this->tableName] SET $fieldUpdateInQuery WHERE $this->primaryKeyName = ?";
                    $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $updateQuery, array(
                        &$primaryKeyValue
                    ));

                    if (!sqlsrv_execute($statement)) {
                        die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to update
                    }

                    $this->isDirty = false;
                }
            }
        }
    }

    /**
     * @param $fieldName
     * @param bool $ignoreIsLoaded
     * @return mixed
     */
    protected function get($fieldName, $ignoreIsLoaded = false) {
        if(property_exists($this, $fieldName)) {
            $primaryKeyValue = $this->getPrimaryKeyField();
            if($ignoreIsLoaded === false && $this->isLoaded === false && !empty($primaryKeyValue)) { //Enables lazy loading
                if($fieldName !== $this->primaryKeyName && ($this->isFieldInDatabase($fieldName) || $ignoreIsLoaded === false)) {
                    $this->load($primaryKeyValue);
                    return $this->$fieldName;
                }
            } else {
                return $this->$fieldName;
            }
            return $this->$fieldName;
        } else {
            die("Property '$fieldName' doesn't exists in class '".get_class($this)."'");
        }
    }

    /**
     * @param $fieldName
     * @param $value
     * @param bool $ignoreIsDirty
     * @return mixed
     */
    protected function set($fieldName, $value, $ignoreIsDirty = false) {
        if(property_exists($this, $fieldName)) {
            if($value !== $this->$fieldName) {
                $this->$fieldName = $value;
                if($ignoreIsDirty === false) {
                    $this->isDirty = true;
                }
            }
        } else {
            die("Property '$fieldName' doesn't exists in class '".get_class($this)."'");
        }
    }

    /**
     * @param $fieldName
     * @return bool
     */
    private function isFieldInDatabase($fieldName) {
        foreach ($this->databaseFields as $type) {
            foreach ($type as $databaseField => $databaseType) {
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
                $databaseFieldsWithValues[$databaseField] = $this->databaseHelper->prepareString($this->get($databaseField, true));
            }
        }

        return $databaseFieldsWithValues; //Removes the last ', '
    }

    /**
     * @param $databaseFields string
     * @return null
     */
    private function prepareDatabaseFieldsForUpdate($databaseFields) {
        $updateFormat = "";
        foreach ($databaseFields as $key => $value) {
            $updateFormat .= "$key = $value, ";
        }

        if(!empty($updateFormat)) {
            return substr($updateFormat, 0, -2); //Remove the ' ,' at the end
        } else {
            die("The model class '$this->tableName' isn't setup properly");
        }
    }

    private function setPrimaryKeyField($value) {
        $primaryKeyName = $this->primaryKeyName;
        $this->$primaryKeyName = $value;
    }

    private function getPrimaryKeyField() {
        $primaryKeyName = $this->primaryKeyName;
        return $this->$primaryKeyName;
    }

    public function mergeQueryData($assocQueryResultArray) {
        foreach ($assocQueryResultArray as $key => $value) {
            if($this->isFieldInDatabase($key) === true) {
                $this->set($key, $value, true);
            }
        }
    }

    /**
     * @param $currentObjectDataFields string[]
     * @return bool
     */
    private function areFieldValuesValid($currentObjectDataFields)
    {
        //TODO: Add REGEX functionality
        foreach ($this->databaseFields["required"] as $fieldKey => $fieldValue) {
            if(!array_key_exists($fieldKey, $currentObjectDataFields) || empty($currentObjectDataFields[$fieldKey])) {
                return false;
            }
        }

        return true;
    }
}