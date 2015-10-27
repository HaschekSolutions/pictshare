<!DOCTYPE html>
<!--[if IEMobile 7 ]>    <html class="no-js iem7"> <![endif]-->
<!--[if (gt IEMobile 7)|!(IEMobile)]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <title><?php echo $title; ?></title>
        <meta name="description" content="">
        <meta name="HandheldFriendly" content="True">
        <meta name="MobileOptimized" content="320">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="cleartype" content="on">
        <?php echo $meta; ?>
        <link rel="stylesheet" href="/css/normalize.css">
        <link rel="stylesheet" href="/css/pictshare.css">
        <script src="/js/vendor/modernizr-2.6.2.min.js"></script>
        <!--<script src="/js/highslide-full.js"></script>-->
        <script type="text/javascript" src="/js/jquery-2.1.0.min.js"></script>
        <TITLE>PictShare - Free picture hosting</TITLE>

		<meta name="description" content="Free picture sharing, linking and tracking">
		<meta name="keywords" content="picture, share, hosting, free">
		<meta name="robots" content="index, follow">
		<meta name="copyright" content="Haschek Solutions">
		<meta name="language" content="EN,DE">
		<meta name="author" content="Haschek Solutions">
		<meta name="distribution" content="global">
		<meta name="rating" content="general">

    </HEAD>
    <BODY>
      <div class="mitte" id="overall">
        <a href="/"><div id="header">
            <div style="padding:15px;"><div class="rechts"><?php echo $slogan; ?></div></div>
        </div></a>
        <div id="content">
            <?php echo $content?>
        </div>
        <div class="content"><center>(c)<?php echo date("y");?> by<br/><a href="http://haschek-solutions.com" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></center></div>
        <div id="footer"></div>
      </div>
    </BODY>
</HTML>
