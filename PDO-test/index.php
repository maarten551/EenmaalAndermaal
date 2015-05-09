<html>
<body>

<?php

include 'MSSQLSettings.php';

try {
    $connParameters = array(
        "Database" => MSSQLSettings::$databaseName,
        "UID" => MSSQLSettings::$username,
        "PWD" => MSSQLSettings::$password
    );

    $conn = sqlsrv_connect(MSSQLSettings::$databaseHost, $connParameters);

    if($conn) {
        echo "Success";
    } else {
        echo "Connection could not be established.<br />";
        die( print_r( sqlsrv_errors(), true));
    }

    $query = "SELECT * FROM [dbo].[User] WHERE username != ?";
    $stmt = sqlsrv_prepare($conn, $query, array("testUser3"));

    if(!sqlsrv_execute($stmt))
        die( print_r( sqlsrv_errors()[0]["message"], true));

    if(sqlsrv_has_rows($stmt)) {
        while($row = sqlsrv_fetch_object($stmt)) {
            var_dump($row);
        }
    }

    /*$conn = new PDO( "sqlsrv:server=".MSSQLSettings::$databaseHost.";Database=".MSSQLSettings::$databaseName, MSSQLSettings::$username, MSSQLSettings::$password);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $statement = $conn->prepare("SELECT * FROM USERS WHERE SEX = :SEX");
    $statement->execute(array(":SEX" => "M"));
    while($row = $statement->fetch(PDO::FETCH_OBJ)) {
        echo "<h2>".$row->USERNAME."</h2>";
    }*/
} catch(Exception $e) {
    die($e->getMessage());
}

?>

</body>
</html>