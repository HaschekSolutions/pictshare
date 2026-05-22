<!DOCTYPE html>
<html class="no-js">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo (defined('TITLE')?TITLE:'PictShare Audio Player'); ?></title>
        
        <!-- Bootstrap -->
        <link href="/css/bootstrap.min.css" rel="stylesheet">

        <!-- PictShare overwrites -->
        <link href="/css/pictshare.css" rel="stylesheet">
        
        <style>
            #audiocontainer {
                text-align: center;
                padding: 50px 0;
            }
            audio {
                width: 100%;
                max-width: 600px;
            }
            .audio-info {
                margin-top: 20px;
                color: #666;
            }
        </style>

		<meta name="description" content="Free Audio sharing">
		<meta name="keywords" content="audio, share, hosting, free">
		<meta name="robots" content="index, follow">
		<meta name="copyright" content="Haschek Solutions">
		<meta name="language" content="EN,DE">
		<meta name="author" content="Haschek Solutions">
		<meta name="distribution" content="global">
        <meta name="rating" content="general">
    </HEAD>
    <BODY>

        <div class="container" id="headcontainer">

                    <a href="/"><img src="/css/imgs/logo/horizontal3.png" /></a>
                    <h4><?php echo $slogan; ?></h4>
                    
                    <div class="well" id="audiocontainer">
                        <audio controls>
                            <source src="/raw/<?php echo $hash?>" type="audio/<?php 
                                switch($extension) {
                                    case 'mp3': echo 'mpeg'; break;
                                    case 'm4a': echo 'mp4'; break;
                                    default: echo $extension;
                                }
                            ?>">
                            Your browser does not support the audio element.
                        </audio>
                        
                        <div class="audio-info">
                            File type: <?php echo strtoupper($extension); ?> | Size: <?php echo $filesize; ?>
                        </div>

                        <div style="margin-top: 20px;">
                            <a class="btn btn-primary" href="/raw/<?php echo $hash?>">Raw</a>
                            <a class="btn btn-primary" href="/download/<?php echo $hash?>">Download</a>
                        </div>
                    </div>
            
            <footer>(c)<?php echo date("y");?> by<br/><a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></footer>
        </div>
      
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script src="/js/bootstrap.min.js"></script>
    </BODY>
</HTML>