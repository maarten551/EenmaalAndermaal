<?php


namespace src\classes\Models;


use src\classes\DatabaseHelper;

class Rubric extends Model {
    protected $id;
    protected $name;
    /**
     * @var int
     * id of object $parentRubric
     */
    protected $childOfRubric;
    /**
     * @var Rubric
     */
    protected $parentRubric;
    protected $sortOrder;
    /**
     * @var Rubric[]
     */
    protected $children;

    public function __construct(DatabaseHelper $databaseHelper, $primaryKeyValue = null) {
        parent::__construct($databaseHelper);
        $this->tableName = "Rubric";
        $this->primaryKeyName = "id";
        $this->hasIdentity = true;
        $this->databaseFields["required"]["name"] = "quote";
        $this->databaseFields["required"]["childOfRubric"] = "quote";
        $this->databaseFields["required"]["sortOrder"] = "quote";

        $this->setId($primaryKeyValue);
    }

    public function getChildren() {
        if($this->children === null) {
            $query = "SELECT id FROM rubric WHERE childOfRubric = ?";
            $statement = sqlsrv_prepare($this->databaseHelper->getDatabaseConnection(), $query, array(&$this->id));
            sqlsrv_execute($statement);
            if(sqlsrv_has_rows($statement)) {
                $this->children = array();
                while ($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                    $child = new Rubric($this->databaseHelper, $row["id"]);
                    $child->setParentRubric($this);
                    $child->getName();
                    $this->children[] = $child;
                }
            }
        }

        return $this->children;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->get("sortOrder");
    }

    /**
     * @param mixed $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->set("sortOrder", $sortOrder);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->get("name");
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->set("name", $name);
    }

    /**
     * @return Rubric
     */
    public function getParentRubric()
    {
        if($this->parentRubric === null && $this->get("childOfRubric") !== null) {
            $this->parentRubric = new Rubric($this->databaseHelper, $this->get("childOfRubric"));
        }

        return $this->parentRubric;
    }

    /**
     * @param Rubric $parentRubric
     */
    public function setParentRubric($parentRubric)
    {
        $this->parentRubric = $parentRubric;
        $this->childOfRubric = $parentRubric->getId();
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