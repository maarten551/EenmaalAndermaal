<?php


namespace src\classes\Models;


use src\classes\DatabaseHelper;

class Question extends Model {
    protected $id;
    protected $questionText;

    public function __construct(DatabaseHelper $databaseHelper, $primaryKeyValue = null) {
        parent::__construct($databaseHelper);
        $this->tableName = "Question";
        $this->primaryKeyName = "id";
        $this->hasIdentity = true;
        $this->databaseFields["required"]["id"] = "quote";
        $this->databaseFields["required"]["questionText"] = "quote";

        $this->setId($primaryKeyValue);
    }

    /**
     * @return mixed
     */
    public function getQuestionText()
    {
        return $this->get("questionText");
    }

    /**
     * @param mixed $questionText
     */
    public function setQuestionText($questionText)
    {
        $this->set("questionText", $questionText);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    private function setId($id) {
        $this->id = $id;
    }
}