<?php
namespace src\classes;

use src\classes\DatabaseHelper\DatabaseSettings;

class DatabaseHelper {
    private $databaseConnection;

    public function __construct() {
        if($this->connectToDatabase() === false) {
            echo "Connecting to database failed: <br />";
            die( print_r( sqlsrv_errors(), true));
            //TODO: Create a correct error? Maybe an exception?
        }
    }

    /**
     * @return Boolean
     */
    private function connectToDatabase() {
        if($this->databaseConnection === null) {
            $connectionParameters = array(
                "Database" => DatabaseSettings::$databaseName,
                "UID" => DatabaseSettings::$username,
                "PWD" => DatabaseSettings::$password
            );

            $connection = sqlsrv_connect(DatabaseSettings::$databaseHost, $connectionParameters);
            if($connection) {
                $this->databaseConnection = $connection;
                return true;
            }
        }

        return false;
    }

    public function getLastInsertedId($queryStatement) {
        sqlsrv_next_result($queryStatement);
        sqlsrv_fetch($queryStatement);
        return sqlsrv_get_field($queryStatement, 0);
    }

    /**
     * @return Mixed
     */
    public function getDatabaseConnection() {
        return $this->databaseConnection;
    }

    public function prepareString($value)
    {
        if (is_string($value)) {
            $value = trim($value);
            $value = htmlentities($value);
            $value = $this->mssql_escape($value);
        }

        return $value;
    }

    public function closeConnection() {
        sqlsrv_close($this->databaseConnection);
    }

    /**
     * @param $data
     * @return string
     * @Source: http://stackoverflow.com/questions/574805/how-to-escape-strings-in-sql-server-using-php
     *
     * Transforms a string in a hexdecimal value, this value will be converted back by MSSQL once inserted or updated
     */
    private function mssql_escape($data)
    {
        if (is_numeric($data)) {
            return $data;
        }
        $unpacked = unpack('H*hex', $data);
        return '0x' . $unpacked['hex'];
    }
}