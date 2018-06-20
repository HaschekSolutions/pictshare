<!DOCTYPE html>
<!--[if IEMobile 7 ]>    <html class="no-js iem7"> <![endif]-->
<!--[if (gt IEMobile 7)|!(IEMobile)]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <title>Album - <?php echo(defined('TITLE') ? TITLE : 'PictShare image hosting'); ?></title>
        <meta name="description" content="">
        <meta name="HandheldFriendly" content="True">
        <meta name="MobileOptimized" content="320">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="cleartype" content="on">
        <meta name="description" content="Free image sharing, linking and tracking">
        <meta name="keywords" content="image, share, hosting, free">
        <meta name="robots" content="index, follow">
        <meta name="copyright" content="Haschek Solutions">
        <meta name="language" content="EN,DE">
        <meta name="author" content="Haschek Solutions">
        <meta name="distribution" content="global">
        <meta name="rating" content="general">
        <base target="_blank" />

        <style type="text/css">
            body {
                background: none transparent;
            }
            .picture {
                <?php
                if ($data['responsive'] === true) {
                    echo '  display: block;
                            max-width: 100%;
                            height: auto;
                            padding-bottom:10px;';
                } else {
                    echo 'padding:7px;';
                }
                ?>
            }
            
        </style>

    </HEAD>
    <BODY>
        <?php echo $content?>
    </BODY>
</HTML>
