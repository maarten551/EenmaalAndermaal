<?php


namespace src\classes;


use src\classes\Models\Item;
use src\classes\Models\Rubric;

class ProductPagination {
    private $amountOfProductsPerPage;
    private $currentPageNumber;
    /**
     * @var Rubric
     */
    private $rubricFilter;
    private $findInTitleFilter;
    private $totalAmountOfProductsInCriteria = 0;

    /**
     * @param $amountOfProductsPerPage
     * @param int $currentPageNumber
     * @param string $findInTitleFilter
     * @param Rubric $rubricFilter
     */
    public function __construct($amountOfProductsPerPage, $currentPageNumber = 1, $findInTitleFilter = '', $rubricFilter = null) {
        $this->amountOfProductsPerPage = $amountOfProductsPerPage;
        $this->currentPageNumber = $currentPageNumber;
        $this->rubricFilter = $rubricFilter;
        $this->findInTitleFilter = $findInTitleFilter;
    }

    /**
     * @param $databaseHelper
     * @param $amountOfProducts
     * @return Models\Item[]
     */
    public function getProducts($databaseHelper) {
        $storedProcedureParameters = array(
            array(
                ($this->rubricFilter !== null) ? $this->rubricFilter->getId() : null,
                SQLSRV_PARAM_IN
            ),
            array(
                (($this->currentPageNumber-1) * $this->amountOfProductsPerPage)+1,
                SQLSRV_PARAM_IN
            ),
            array(
                (($this->currentPageNumber) * $this->amountOfProductsPerPage),
                SQLSRV_PARAM_IN
            ),
            array(
                $this->findInTitleFilter,
                SQLSRV_PARAM_IN
            ),
            array(
                &$this->totalAmountOfProductsInCriteria,
                SQLSRV_PARAM_INOUT
            )
        );

        $statement = sqlsrv_query($databaseHelper->getDatabaseConnection(), "{call sp_getItemsInRubric(?, ?, ?, ?, ?) }", $storedProcedureParameters);
        if($statement === false) {
            echo "Error in executing statement 3.\n";
            die( print_r( sqlsrv_errors(), true));
        } else {
            /** @var $rubrics Item[] */
            $items = array();
            while($row = sqlsrv_fetch_array($statement, SQLSRV_FETCH_ASSOC)) {
                $item = new Item($databaseHelper, $row["id"]);
                $item->mergeQueryData($row);
                $items[] = $item;
            }

            return $items;
        }
    }

    /**
     * @return string
     */
    public function getFindInTitleFilter()
    {
        return $this->findInTitleFilter;
    }

    /**
     * @param string $findInTitleFilter
     */
    public function setFindInTitleFilter($findInTitleFilter)
    {
        $this->findInTitleFilter = $findInTitleFilter;
    }

    /**
     * @return mixed
     */
    public function getAmountOfProductsPerPage()
    {
        return $this->amountOfProductsPerPage;
    }

    /**
     * @param mixed $amountOfProductsPerPage
     */
    public function setAmountOfProductsPerPage($amountOfProductsPerPage)
    {
        $this->amountOfProductsPerPage = $amountOfProductsPerPage;
    }

    /**
     * @return int
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    /**
     * @param int $currentPageNumber
     */
    public function setCurrentPageNumber($currentPageNumber)
    {
        $this->currentPageNumber = $currentPageNumber;
    }

    /**
     * @return Rubric
     */
    public function getRubricFilter()
    {
        return $this->rubricFilter;
    }

    /**
     * @param Rubric $rubricFilter
     */
    public function setRubricFilter($rubricFilter)
    {
        $this->rubricFilter = $rubricFilter;
    }

    /**
     * @return int
     */
    public function getTotalAmountOfProductsInCriteria()
    {
        return $this->totalAmountOfProductsInCriteria;
    }

    /**
     * @param int $totalAmountOfProductsInCriteria
     */
    public function setTotalAmountOfProductsInCriteria($totalAmountOfProductsInCriteria)
    {
        $this->totalAmountOfProductsInCriteria = $totalAmountOfProductsInCriteria;
    }


}