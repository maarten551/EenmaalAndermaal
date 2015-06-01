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
     * @param $databaseHelper DatabaseHelper
     * @param $questionText
     * @return Question
     */
    public static function GET_BY_QUESTION_TEXT($databaseHelper, $questionText) {
        if(!empty($questionText)) {
            $selectQuery = "SELECT TOP 1 id, questionText FROM [question] WHERE questionText = ?";
            $statement = sqlsrv_prepare($databaseHelper->getDatabaseConnection(), $selectQuery, array(
                &$questionText
            ));
            sqlsrv_execute($statement);
            if($statement !== false) {
                if (sqlsrv_has_rows($statement)) {
                    $row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC);
                    $question = new Question($databaseHelper, $row["id"]);
                    $question->mergeQueryData($row);

                    return $question;
                }
            } else {
                die( print_r( sqlsrv_errors(), true));
            }
        }

        return null;
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