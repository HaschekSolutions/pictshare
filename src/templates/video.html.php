<!doctype html>
<html>
    <head>
        <title><?= (defined('TITLE')?TITLE:'PictShare image hosting'); ?></title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta name="copyright" content="Copyright <?= date("Y"); ?> PictShare" />
        <meta id="viewport" name="viewport" content="width=<?= $width ?>, user-scalable=yes" />
        <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>

        <style type="text/css">
            *{margin:0px;padding:0px;}
        </style>
        
        <link rel="alternate" type="application/json+oembed" href="<?= URL; ?>backend.php?a=oembed&t=json&url=<?= rawurlencode(URL.$hash); ?>" title="PictShare" />
                
        <link rel="canonical"                 href="<?= URL.$hash; ?>" />

        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

        <meta property="og:site_name"         content="<?= (defined('TITLE')?TITLE:'PictShare mp4 hosting'); ?>" />
        <meta property="og:url"               content="<?= URL.$hash; ?>" />
        <meta property="og:title"             content="<?= (defined('TITLE')?TITLE:'PictShare mp4 hosting'); ?> MP4" />
        <meta property="og:type"              content="video.other" />
        
        <meta property="og:image"             content="<?= URL.'preview/'.$hash; ?>" />
        <meta property="og:image:width"       content="<?= $width ?>" />
        <meta property="og:image:height"      content="<?= $height ?>" />
        <meta property="og:description"       content="MP4 Video" />
        <meta property="og:video"             content="<?= URL.$hash; ?>" />
        <meta property="og:video:secure_url"  content="<?= URL.$hash; ?>" />
        <meta property="og:video:type"        content="application/x-shockwave-flash" />
        <meta property="og:video:width"       content="<?= $width ?>" />
        <meta property="og:video:height"      content="<?= $height ?>" />
        <meta property="og:video:type"        content="video/mp4" />
        <meta property="og:video:width"       content="<?= $width ?>" />
        <meta property="og:video:height"      content="<?= $height ?>" />
    </head>
    <body id="body">

        <div id="container">
            <video id="video" poster="<?= URL.$url.'/preview/'.$hash; ?>" preload="auto" autoplay="autoplay" controls muted="muted" loop="loop" webkit-playsinline>   
                <source src="<?= URL.$url.'/raw' ?>" type="video/mp4">
                <?php
                    if(file_exists(getDataDir().DS.$hash.DS.'webm_1.'.$hash))
                        echo '<source src="'.URL.'raw/webm/'.$hash.'" type="video/webm">'."\n";
                    if(file_exists(getDataDir().DS.$hash.DS.'ogg_1.'.$hash))
                        echo '<source src="'.URL.'raw/ogg/'.$hash.'" type="video/ogg">'."\n";
                ?>
            </video>
        </div>
            <small><?= $filesize; ?> <a href="<?= URL.$url.'/raw' ?>">Raw</a> <a href="<?= URL.$url.'/download' ?>">Download</a></a></small>
            
            <script>
                var hadToResizeW = false;
                var hadToResizeH = false;
                var video = document.getElementById('video');
                video.addEventListener('click',function(){
                    video.play();
                },false);
                var $video  = $('video'),
                    $window = $(window); 
                //check video size as soon as the page has finished loading
                jQuery(window).load(function () {
                    resizeMe();
                });
                
                
                //if the windows size has changed, check video sizes again
                $(window).resize(function(){
                    resizeMe();
                }).resize();
                function resizeMe()
                {
                    if($window.width() < $video.width() || hadToResizeW===true)
                    {
                        hadToResizeW = true;
                        $video.width($window.width());
                    }
                        
                    if($window.height() < $video.height() || hadToResizeH===true)
                    {
                        hadToResizeH = true;
                        $video.height($window.height());
                    }
                }
            </script>

        

    </body>
</html>