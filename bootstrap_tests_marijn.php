
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

        <!-- het inladen van de bootstrap stylesheets -->
        <link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="css/marijn.tests.css">


        <!-- het inladen van de javascripts -->
        <script src="js/jquery-1.11.2.min.js"></script>
        <script src="js/bootstrap.min.js"></script>


        <title>Bootstrap Tests</title>
    </head>

    <body>

    <!-- de navbar wordt gemaakt -->
    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#"> <img src="images/logo2.png" alt="Eenmaal Andermaal" width="50" height="50"></a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <form class="navbar-form navbar-left" role="search">
                    <div class="form-group">
                        <input type="text" class="form-control" placeholder="Search">
                    </div>
                    <button type="submit" class="btn btn-default">Submit</button>

                </form>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="#">Geavanceerd zoeken</a></li>
                    <li class="divider-vertical"></li>
                    <li><a href="#">Inloggen</a></li>
                    <li><a href="#">Aanmelden</a></li>
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>

        <!-- aanmaken van witte contentvak -->
        <div class="contentvak">

            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-5ths" style= "background-color: red"   >start </div>
                <div class="col-xs-12 col-sm-6 col-md-5ths" style = "background-color: green" >start2</div>
                <div class="col-xs-12 col-sm-6 col-md-5ths" style = "background-color: blue"  >start3</div>
                <div class="col-xs-12 col-sm-6 col-md-5ths" style = "background-color: yellow">start4</div>
                <div class="col-xs-12 col-sm-6 col-md-5ths" style = "background-color: orange">start5</div>
            </div>

        </div>
    </body>

</html>