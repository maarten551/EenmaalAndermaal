<?php


namespace src\classes\models;


use src\classes\DatabaseHelper;

class Question extends Model {
    protected $id;
    protected $questionText;

    public function __construct(DatabaseHelper $databaseHelper) {
        parent::__construct($databaseHelper);
        $this->tableName = "Question";
        $this->primaryKeyName = "id";
        $this->hasIdentity = true;
        $this->databaseFields["required"]["questionText"] = "quote";
        $this->setQuestionText("This is a test question");
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
        $this->questionText = $questionText;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


}