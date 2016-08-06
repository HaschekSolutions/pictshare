<!doctype html>
<html>
    <head>
        <title>PictShare</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta name="copyright" content="Copyright <?php echo date("Y"); ?> PictShare" />
        <meta id="viewport" name="viewport" content="width=<?php echo $width ?>, user-scalable=yes" />
        <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>

        <style type="text/css">
            *{margin:0px;padding:0px;}
        </style>
        
        <link rel="alternate" type="application/json+oembed" href="<?php echo DOMAINPATH; ?>backend.php?a=oembed&t=json&url=<?php echo rawurlencode(DOMAINPATH.$hash); ?>" title="PictShare" />
                
        <link rel="canonical"                 href="<?php echo DOMAINPATH.$hash; ?>" />

        <meta property="og:site_name"         content="Imgur" />
        <meta property="og:url"               content="<?php echo DOMAINPATH.$hash; ?>" />
        <meta property="og:title"             content="PictShare MP4" />
        <meta property="og:type"              content="video.other" />
        
        <meta property="og:image"             content="<?php echo DOMAINPATH.'preview/'.$hash; ?>" />
        <meta property="og:image:width"       content="<?php echo $width ?>" />
        <meta property="og:image:height"      content="<?php echo $height ?>" />
        <meta property="og:description"       content="PictShare MP4 Video" />
        <meta property="og:video"             content="<?php echo DOMAINPATH.$hash; ?>" />
        <meta property="og:video:secure_url"  content="<?php echo DOMAINPATH.$hash; ?>" />
        <meta property="og:video:type"        content="application/x-shockwave-flash" />
        <meta property="og:video:width"       content="<?php echo $width ?>" />
        <meta property="og:video:height"      content="<?php echo $height ?>" />
        <meta property="og:video:type"        content="video/mp4" />
        <meta property="og:video:width"       content="<?php echo $width ?>" />
        <meta property="og:video:height"      content="<?php echo $height ?>" />
    </head>
    <body id="body">

        <div id="container img-responsive">
            <video id="video" poster="<?php echo DOMAINPATH.'preview/'.$hash; ?>" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline>   
                <source src="<?php echo DOMAINPATH.'raw/mp4/'.$hash; ?>" type="video/mp4">
                <source src="<?php echo DOMAINPATH.'raw/webm/'.$hash; ?>" type="video/webm"> 
            </video>
        </div>
            <small><?php echo $filesize; ?></small>
            
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


                        

                    /*
                    var height = $window.height();
                    $video.css('height', height);
                
                    
                    var videoWidth = $video.width(),
                        windowWidth = $window.width(),
                    marginLeftAdjust =   (windowWidth - videoWidth) / 2;
                
                    $video.css({
                        'height': height, 
                        'marginLeft' : marginLeftAdjust
                    });*/
                }
            </script>

        

    </body>
</html>
