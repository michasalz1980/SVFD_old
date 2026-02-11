<?php
    header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">

<HTML>
<HEAD>
    <TITLE>Freibad Dabringhausen - Passwort zurücksetzen</TITLE>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <!-- Optional theme -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
    <!-- Latest compiled and minified JavaScript -->
    <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="js/md5.js"></script>
    <script src="js/main.js"></script>
    <link rel="stylesheet" href="css/signin.css">
    <link rel="stylesheet" href="css/general.css">
</HEAD>
<BODY>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="container" id="header" style="max-width: 1013px;">
            <a href="/assets/images/logo.gif" id="logo"></a>

            <div id="navi">
                <?php if (isset($_SESSION['id'])) { ?>
                    <!--<a href="api/logout" style="color: white; float: right;">Ausloggen</a>-->
                    <input type="button" value="Ausloggen"  onClick="location.href='/schedule/api/logout'" class="btn btn-primary pull-right"/>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<div class="container" id="login">

    <form role="form" class="form-signin">
        <h2 class="form-signin-heading">Passwort zurücksetzen</h2>
        <input id="email" type="email" autofocus="" required="" placeholder="Email Adresse" class="form-control">
        <button id="btnRequestPassword" type="button" class="btn btn-lg btn-primary btn-block">Passwort anfordern</button>
    </form>

</div>


</BODY>
</HTML>