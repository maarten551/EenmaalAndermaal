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

    /**
     * @return Mixed
     */
    public function getDatabaseConnection() {
        return $this->databaseConnection;
    }

    public function prepareString($value) {
        $value = trim($value);
        $value = htmlentities($value);
        $value = mysql_real_escape_string($value);

        return $value;
    }
}