<!DOCTYPE html>
<html class="no-js">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo (defined('TITLE')?TITLE:'PictShare Markdown Viewer'); ?></title>
        
        <!-- Bootstrap -->
        <link href="/css/bootstrap.min.css" rel="stylesheet">

        <!-- PictShare overwrites -->
        <link href="/css/pictshare.css" rel="stylesheet">
        
        <!-- Markdown styling -->
        <style>
            .markdown-body {
                box-sizing: border-box;
                min-width: 200px;
                max-width: 980px;
                margin: 0 auto;
                padding: 45px;
                background-color: #fff;
                border: 1px solid #ddd;
                border-radius: 3px;
            }

            @media (max-width: 767px) {
                .markdown-body {
                    padding: 15px;
                }
            }
            
            .markdown-body img {
                max-width: 100%;
            }
            
            .markdown-body pre {
                background-color: #f6f8fa;
                border-radius: 3px;
                padding: 16px;
            }
        </style>

		<meta name="description" content="Free Markdown sharing">
		<meta name="keywords" content="markdown, share, hosting, free">
		<meta name="robots" content="index, follow">
		<meta name="copyright" content="Haschek Solutions">
		<meta name="language" content="EN,DE">
		<meta name="author" content="Haschek Solutions">
		<meta name="distribution" content="global">
        <meta name="rating" content="general">
        <link rel="stylesheet" href="/js/styles/rainbow.css">
    </HEAD>
    <BODY>

        <div class="container" id="headcontainer">

                    <a href="/"><img src="/css/imgs/logo/horizontal3.png" /></a>
                    <h4><?php echo $slogan; ?></h4>
                    <div class="well">
                            <a class="btn btn-primary" href="/raw/<?php echo $hash?>">Raw</a>
                            <a class="btn btn-primary" href="/download/<?php echo $hash?>">Download</a>
                    </div>
                    
                    <div class="markdown-body">
                        <?php echo $content; ?>
                    </div>
            
            <footer>(c)<?php echo date("y");?> by<br/><a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></footer>
        </div>
      
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>

        <script src="/js/highlight.pack.js"></script>
        <script>hljs.initHighlightingOnLoad();</script>
    </BODY>
</HTML>