<!DOCTYPE html>
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
    <link href="/css/bootstrap-table.min.css" rel="stylesheet">
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

    <div id="main" class="container hv-100">
        <?=$main;?>
    </div>

    <div class="footer">
        <div class="container text-center">
            <p>created by <a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></p>
        </div>
    </div>


    <script src="/js/jquery.min.js"></script>
    <script src="/js/bootstrap.bundle.min.js"></script>
    <script src="/js/bootstrap-table.min.js"></script>
    <script src="/js/dropzone.js"></script>
    <script src="/js/highlight.pack.js"></script>
    <script src="/js/pictshare.js"></script>
</BODY>

</HTML>