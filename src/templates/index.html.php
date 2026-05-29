<!DOCTYPE html>
<html class="no-js"> <!--<![endif]-->

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PictShare - the smart CDN</title>

    <!-- Bootstrap -->
    <link href="<?= assetUrl('/css/bootstrap.min.css') ?>" rel="stylesheet">

    <!-- PictShare overwrites -->
    <link href="<?= assetUrl('/css/pictshare.css') ?>" rel="stylesheet">
    <link href="<?= assetUrl('/css/dropzone.css') ?>" rel="stylesheet">
    <link href="<?= assetUrl('/css/hljs-dracula.css') ?>" rel="stylesheet">


    <!-- github-fork-ribbon-css
	     https://simonwhitaker.github.io/github-fork-ribbon-css/ -->
    <link href="<?= assetUrl('/css/gh-fork-ribbon.min.css') ?>" rel="stylesheet">

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
            <p>created by <a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a> - <a href="https://github.com/HaschekSolutions/pictshare" target="_blank">GitHub</a> - <a href="/report">Report abuse</a> - <?=defined('PICTSHARE_VERSION')?htmlspecialchars(PICTSHARE_VERSION):'git'?></p>
        </div>
    </div>


    <script src="<?= assetUrl('/js/jquery.min.js') ?>"></script>
    <script src="<?= assetUrl('/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="https://unpkg.com/htmx.org@2.0.4" integrity="sha384-HGfztofotfshcF7+8n44JQL2oJmowVChPTg48S+jvZoztPfvwD79OC/LTtG6dMp+" crossorigin="anonymous"></script>
    <script src="<?= assetUrl('/js/dropzone.js') ?>"></script>
    <script src="<?= assetUrl('/js/highlight.pack.js') ?>"></script>
    <script src="<?= assetUrl('/js/uploads-store.js') ?>"></script>
    <script src="<?= assetUrl('/js/pictshare.js') ?>"></script>
    <script src="<?= assetUrl('/js/qrcode.min.js') ?>"></script>
</BODY>

</HTML>