<!doctype html>
<html>
    <head>
        <title>PictShare</title>
        <meta http-equiv="content-type" content="text/html;charset=utf-8" />
        <meta name="copyright" content="Copyright <?php echo date("Y"); ?> PictShare" />
        <meta id="viewport" name="viewport" content="width=<?php echo $width ?>, user-scalable=yes" />

        <style type="text/css">
            /*#content, #video {
                width:  <?php echo $width ?>px;
                height: <?php echo $height ?>px;
            }*/

            video {  
               width:100%; 
               max-width:<?php echo $width ?>px; 
               height:auto; 
            }
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
        <div id="content">
            <video id="video" poster="<?php echo DOMAINPATH.'preview/'.$hash; ?>" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline>
                <source src="<?php echo DOMAINPATH.'raw/'.$hash; ?>" type="video/mp4">
            </video>
            <small><?php echo $filesize; ?></small>

        </div>

    </body>
</html>
