<!--
 * Created by PhpStorm.
 * User: Marijn
 * Date: 1-5-2015
 * Time: 09:28
 -->
<html>
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- het inladen van de stylesheets -->
    <link rel="stylesheet" type="text/css" href="../css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="../css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../css/standard_style.css">

    <!-- het inladen van de javascripts -->
    <script src="../js/jquery-1.11.2.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>

    <title>Bootstrap Tests</title>
</head>

<body>
<div id="contentvak">
    <div id = "push">
        <?php
        require 'require/navbar.php';
        require 'require/menu.php';
        ?>
    </div>

    <?php
    require 'require/footer.php'
    ?>
</div>
</body>

</html>