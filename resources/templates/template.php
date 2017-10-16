<!DOCTYPE html>
<!--[if IEMobile 7 ]>
<html class="no-js iem7"> <![endif]-->
<!--[if (gt IEMobile 7)|!(IEMobile)]><!-->
<html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo( defined('TITLE') ? TITLE : 'PictShare image hosting' ); ?></title>

    <!-- Bootstrap -->
    <link href="<?php echo PATH; ?>resources/assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- PictShare overwrites -->
    <link href="<?php echo PATH; ?>resources/assets/css/pictshare.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <meta name="description" content="Free image sharing, linking and tracking">
    <meta name="keywords" content="image, share, hosting, free">
    <meta name="robots" content="index, follow">
    <meta name="copyright" content="Haschek Solutions">
    <meta name="language" content="EN,DE">
    <meta name="author" content="Haschek Solutions">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">

</HEAD>
<BODY>

<div class="container" id="headcontainer">
    <div class="row">
        <div class="col-md-8">
            <a href="<?php echo PATH; ?>"><img src="<?php echo PATH; ?>resources/assets/css/imgs/logo.png"/></a>
            <h4><?php echo isset($slogan) ? $slogan : ''; ?></h4>
            <div class="well">
                <?php echo isset($content) ? $content : '' ?>
            </div>
        </div>
    </div>

    <footer>
        (c)<?php echo date("y"); ?> by<br/>
        <a href="https://haschek.solutions" target="_blank">
            <img height="30" src="<?php echo PATH; ?>resources/assets/css/imgs/hs_logo.png"/>
        </a>
    </footer>
</div>

<a href="https://github.com/chrisiaut/pictshare">
    <img style="position: absolute; top: 0; left: 0; border: 0;" src="https://camo.githubusercontent.com/82b228a3648bf44fc1163ef44c62fcc60081495e/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f7265645f6161303030302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_red_aa0000.png">
</a>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="<?php echo PATH; ?>resources/assets/js/bootstrap.min.js"></script>
</BODY>
</HTML>
