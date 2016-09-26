<!DOCTYPE html>
<!--[if IEMobile 7 ]>    <html class="no-js iem7"> <![endif]-->
<!--[if (gt IEMobile 7)|!(IEMobile)]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <title><?php echo (defined(TITLE)?TITLE:'PictShare image hosting'); ?></title>
        <meta name="description" content="">
        <meta name="HandheldFriendly" content="True">
        <meta name="MobileOptimized" content="320">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="cleartype" content="on">
        <?php echo $meta; ?>
        <link rel="stylesheet" href="/css/normalize.css">
        <link rel="stylesheet" href="/css/pictshare.css">
        <script src="/js/vendor/modernizr-2.6.2.min.js"></script>
        <script type="text/javascript" src="/js/jquery-2.1.0.min.js"></script>

		<meta name="description" content="Free image sharing, linking and tracking">
		<meta name="keywords" content="image, share, hosting, free">
		<meta name="robots" content="index, follow">
		<meta name="copyright" content="Haschek Solutions">
		<meta name="language" content="EN,DE">
		<meta name="author" content="Haschek Solutions">
		<meta name="distribution" content="global">
		<meta name="rating" content="general">

    </head>
    <body>
      <div class="mitte" id="overall">
        <a href="/"><div id="header">
            <div style="padding:15px;"><div class="rechts"><?php echo $slogan; ?></div></div>
        </div></a>
        <div id="content">
            <?php echo $content?>
        </div>
        <div class="content"><center>(c)<?php echo date("y");?> by<br/><a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></center></div>
        <div id="footer"></div>
      </div>
      
      <a href="https://github.com/chrisiaut/pictshare"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/365986a132ccd6a44c23a9169022c0b5c890c387/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f7265645f6161303030302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png"></a>
  </body>
</html>
