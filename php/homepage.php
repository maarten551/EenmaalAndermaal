<!--
 * Created by PhpStorm.
 * User: Marijn
 * Date: 1-5-2015
 * Time: 09:28
 -->
<!doctype html>
<html>
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- het inladen van de stylesheets -->
    <link rel="stylesheet" type="text/css" href="../site/src/css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="../site/src/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../site/src/css/standard_style.css">
    <link rel="stylesheet" type="text/css" href="../site/src/css/homepage.css">

    <!-- het inladen van de javascripts -->
    <script src="../site/src/js/jquery-1.11.2.min.js"></script>
    <script src="../site/src/js/bootstrap.min.js"></script>

    <title>Bootstrap Tests</title>
</head>

<body>
<div id="contentvak">
    <div id = "push">
        <?php
        require 'require/navbar.php';
        require 'require/menu.php';
        ?>
        <div class="row">
            <div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">
                <ul class="list-group" style="font-size: 0.5em">
                    <li class="list-group-item" style="background-color: #68ADBF">Kies een categorie:</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>
                    <li class="list-group-item"><span class="badge">546 </span>Auto's</li>
                    <li class="list-group-item"><span class="badge">324 </span>Meubelen</li>


                </ul>
            </div>
        </div>

        <?php
        require 'require/footer.php'
        ?>
    </div>
</body>

</html>