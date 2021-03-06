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

    /**
     * @param DatabaseHelper $databaseHelper
     */
    public function __construct(DatabaseHelper $databaseHelper)
    {
        $this->databaseHelper = $databaseHelper;
        $this->isDirty = false;
        $this->isLoaded = false;
    }

    protected function load($primaryKeyValue) {
        if ($primaryKeyValue !== null) {
            $databaseFields = $this->getDatabaseFieldsWithValues(); //We need to know the keys for selecting the columns
            $whereClause = "WHERE ".$this->prepareCompositePrimaryKey($primaryKeyValue);
            $selectQuery = "SELECT " . implode(', ', array_keys($databaseFields)) . "
                FROM [$this->tableName]
                $whereClause";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $selectQuery);

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

    public function delete() {
        $whereClause = "WHERE ".$this->prepareCompositePrimaryKey($this->getPrimaryKeyField());
        $query = "DELETE FROM $this->tableName $whereClause";
        $statement = sqlsrv_query($this->databaseHelper->getDatabaseConnection(), $query);
        if($statement !== false) {
            $this->setPrimaryKeyField(null);
        }
    }

    private function prepareCompositePrimaryKey($primaryKeyValue) {
        if(is_array($primaryKeyValue)) {
            $compositeWhereClause = "";

            foreach ($primaryKeyValue as $compositeFieldName) {
                $compositeFieldValue = $this->get($compositeFieldName, true);
                if(empty($compositeFieldValue)) {
                    die("The composite key values weren't supplied in the class ". get_class($this));
                }
                $compositeWhereClause .= "$compositeFieldName = ".$this->databaseHelper->prepareString($compositeFieldValue)." AND ";
            }

            return substr($compositeWhereClause, 0, -5); //Removed the last ' AND '
        }

        return "$this->primaryKeyName = ".$this->databaseHelper->prepareString($primaryKeyValue);
    }

    public function save()
    {
        if ($this->isDirty === true) {
            $currentObjectDataFields = $this->getDatabaseFieldsWithValues();
            if ($this->areFieldValuesValid($currentObjectDataFields)) {
                $primaryKeyValue = $this->getPrimaryKeyField();
                if ($this->isLoaded === false) {
                    if ($primaryKeyValue === null && $this->hasIdentity === true) {//test
                        $insertQuery = "INSERT INTO [$this->tableName] "
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
                    } else if ($this->hasIdentity === false) { //When there is no auto increment, the id needs to be given (and the id doesn't need to set)
                        $insertQuery = "INSERT INTO [$this->tableName] "
                            . "(" . implode(", ", array_keys($currentObjectDataFields)) . ") "
                            . "VALUES (" . implode(", ", $currentObjectDataFields) . ")";
                        $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $insertQuery);

                        if (!sqlsrv_execute($statement)) {
                            die(print_r(sqlsrv_errors()[0]["message"], true)); //Failed to insert
                        }

                        $this->isDirty = false;
                        $this->isLoaded = true;
                    }
                } else {
                    $fieldUpdateInQuery = $this->prepareDatabaseFieldsForUpdate($currentObjectDataFields);
                    $whereClause = "WHERE ".$this->prepareCompositePrimaryKey($primaryKeyValue);
                    $updateQuery = "UPDATE [$this->tableName] SET $fieldUpdateInQuery $whereClause";
                    $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $updateQuery);

                    if (!sqlsrv_execute($statement)) {
                        throw new \Exception(sqlsrv_errors()[0]["message"]); //Failed to update
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

            if($ignoreIsLoaded === false && $this->isLoaded === false && $this->prepareCompositePrimaryKey($primaryKeyValue) !== "") { //Enables lazy loading and also checks if composite keys are filled in
                if($fieldName !== $this->primaryKeyName && ($this->isFieldInDatabase($fieldName) || $ignoreIsLoaded === false)) {
                    $this->load($primaryKeyValue);
                    return $this->$fieldName;
                }
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

    protected function getDatabaseFieldsWithValues() {
        $databaseFieldsWithValues = array();
        foreach ($this->databaseFields as $type) {
            foreach ($type as $databaseField => $databaseType) {
                /**
                 * @var $value \DateTime
                 */
                $value = $this->get($databaseField, true);
                if($value instanceof \DateTime) {
                    $databaseFieldsWithValues[$databaseField] = "'".$value->format("m-d-Y H:i:s")."'";
                } elseif (is_bool($value)) {
                    $databaseFieldsWithValues[$databaseField] = ($value === true) ? 1 : 0;
                } elseif ($value === null) {
                    $databaseFieldsWithValues[$databaseField] = "NULL";
                } else {
                    $databaseFieldsWithValues[$databaseField] = $this->databaseHelper->prepareString($value);
                }
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
            if($value instanceof \DateTime) {
                /**
                 * @var $value \DateTime
                 */
                $updateFormat .= "$key = '".$value->format("m-d-Y H:i:s")."', ";
            } elseif (is_bool($value)) {
                $updateFormat .= "$key = ".($value === true) ? 1 : 0 .", ";
            } else {
                $updateFormat .= "$key = $value, ";
            }
        }

        if(!empty($updateFormat)) {
            return substr($updateFormat, 0, -2); //Remove the ' ,' at the end
        } else {
            die("The model class '$this->tableName' isn't setup properly");
        }
    }

    private function setPrimaryKeyField($value) {
        if(is_array($value)) { //If composite keys
            $this->primaryKeyName = $value;
        } else {
            $primaryKeyName = $this->primaryKeyName;
            $this->$primaryKeyName = $value;
        }
    }

    private function getPrimaryKeyField() {
        if(is_array($this->primaryKeyName)) { //If composite keys
            return $this->primaryKeyName;
        }

        $primaryKeyName = $this->primaryKeyName;
        return $this->$primaryKeyName;
    }

    public function mergeQueryData($assocQueryResultArray) {
        foreach ($assocQueryResultArray as $key => $value) {
            if(property_exists($this, $key)) {
                $this->set($key, $value, true);
            }
        }
        $this->isLoaded = true;
    }

    /**
     * @param $currentObjectDataFields string[]
     * @return bool
     */
    protected function areFieldValuesValid($currentObjectDataFields)
    {
        //TODO: Add REGEX functionality
        foreach ($this->databaseFields["required"] as $fieldKey => $fieldValue) {
            if(!array_key_exists($fieldKey, $currentObjectDataFields) || (empty($currentObjectDataFields[$fieldKey]) && $currentObjectDataFields[$fieldKey] !== 0)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $isLoaded bool
     */
    public function setIsLoaded($isLoaded) {
        $this->isLoaded = $isLoaded;
    }

    /**
     * @return boolean
     */
    public function getIsLoaded()
    {
        return $this->isLoaded;
    }
}