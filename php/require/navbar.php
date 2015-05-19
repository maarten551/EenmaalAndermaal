<?php
/**
 * Created by PhpStorm.
 * User: Marijn
 * Date: 19-5-2015
 * Time: 09:49
 */
    echo '<div class="navbar navbar-default navbar-fixed-top"> <!-- de navbar wordt gemaakt -->
        <div class="container">
            <div class="navbar-header">

                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse"> <!-- de hamburgerbutton voor een verkleind scherm -->
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <a class="navbar-brand" href="Home.php">
                    <img alt="Eenmaal Andermaal" class="img-responsive" src="../images/logo2.png" style="max-width:50px; margin-top: -15px;">
                </a>
            </div>

            <div class="navbar-collapse collapse" id="searchbar"> <!-- content voor in de navbar -->
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="#">Geavanceerd zoeken</a></li>
                    <li><a href="#">Inloggen</a></li>
                    <li><a href="#">Aanmelden</a></li>
                </ul>

                <form class="navbar-form">
                    <div class="form-group" style="display:inline;">
                        <div class="input-group" style="display:table;">
                            <input class="form-control" name="search" placeholder="Zoeken" autocomplete="off" autofocus="autofocus" type="text">
                            <span class="input-group-addon" style="width:1%;"> <span class="glyphicon glyphicon-search"> </span></span>
                        </div>
                    </div>
                </form>
            </div><!--/.nav-collapse -->
        </div>
    </div>';
?>
