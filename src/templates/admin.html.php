<!DOCTYPE html>
<!--[if IEMobile 7 ]>    <html class="no-js iem7"> <![endif]-->
<!--[if (gt IEMobile 7)|!(IEMobile)]><!-->
<html class="no-js"> <!--<![endif]-->

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PictShare - the smart CDN</title>

    <!-- Bootstrap -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <!-- PictShare overwrites -->
    <link href="/css/pictshare.css" rel="stylesheet">
    <link href="/css/dropzone.css" rel="stylesheet">
    <link href="/css/hljs-dracula.css" rel="stylesheet">


    <!-- github-fork-ribbon-css
	     https://simonwhitaker.github.io/github-fork-ribbon-css/ -->
    <link href="/css/gh-fork-ribbon.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

    <script>
        var maxUploadFileSize = <?php echo (int)(ini_get('upload_max_filesize')); ?>
    </script>

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
                <a href="/"><img src="/css/imgs/logo/horizontalv3.png" /></a>
            </div>
        </div>
    </div>

    <div class="container">
        <h2>Admin Panel</h2>
        <?php if (!$_SESSION['admin']) { ?>
            <form method="post" action="/admin">
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password" aria-label="Password" aria-describedby="btn-addn">
                    <button class="btn btn-outline-secondary" type="submit" id="btn-addn">Login</button>
                </div>
            </form>
        <?php } ?>
        <?php if ($_SESSION['admin']) { ?>
            <div class="alert alert-success" role="alert">You are logged in as admin</div>
            <form method="post" action="/admin">
                <button type="submit" name="logout" class="btn btn-danger">Logout</button>
            </form>

            <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="#">Stats</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Review</a>
                </li>
            </ul>
            
        <?php } ?>
    </div>

    <div class="container">
        <footer class="text-center">(c)<?php echo date("y"); ?> by<br /><a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></footer>
    </div>


    <script src="/js/pictshare.js"></script>
</BODY>

</HTML>