<?php


namespace src\classes\Models;


use src\classes\DatabaseHelper;

class File extends Model {
    protected $fileName;
    protected $fileLocation;
    protected $itemId;

    public function __construct(DatabaseHelper $databaseHelper, $fileName, $itemId) {
        parent::__construct($databaseHelper);
        $this->tableName = "File";
        $this->primaryKeyName = array("fileName", "itemId");
        $this->hasIdentity = false;
        $this->databaseFields["required"]["fileName"] = "quote";
        $this->databaseFields["required"]["itemId"] = "quote";
        $this->databaseFields["optional"]["fileLocation"] = "quote";

        $this->setFileName($fileName);
        $this->setItemId($itemId);
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->get("fileName");
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName)
    {
        $this->set("fileName", $fileName);
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->get("itemId");
    }

    /**
     * @param $itemId
     */
    public function setItemId($itemId)
    {
        $this->set("itemId", $itemId);
    }

    public function getFileLocation() {
        return $this->get("fileLocation");
    }

    public function setFileLocation($fileLocation) {
        $this->set("fileLocation", $fileLocation);
    }
}