<html>
<body>

<?php

include 'MSSQLSettings.php';

try {
    $conn = new PDO( "sqlsrv:server=".MSSQLSettings::$databaseHost.";Database=".MSSQLSettings::$databaseName, MSSQLSettings::$username, MSSQLSettings::$password);
    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $statement = $conn->prepare("SELECT * FROM USERS WHERE SEX = :SEX");
    $statement->execute(array(":SEX" => "M"));
    while($row = $statement->fetch(PDO::FETCH_OBJ)) {
        echo "<h2>".$row->USERNAME."</h2>";
    }
} catch( PDOException $e ) {
    die($e->getMessage());
}

?>

</body>
</html>