<!DOCTYPE html>
<!--[if IEMobile 7 ]>    <html class="no-js iem7"> <![endif]-->
<!--[if (gt IEMobile 7)|!(IEMobile)]><!-->
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
    <a class="github-fork-ribbon left-top" href="https://github.com/HaschekSolutions/pictshare" data-ribbon="Fork me on GitHub" title="Fork me on GitHub">Fork me on GitHub</a>

    <div class="container" id="headcontainer">
        <div class="row">
            <div class="col-md-8">
                <a href="/"><img src="/css/imgs/logo/horizontalv3.png" /></a>
                <?php
                if (file_exists(ROOT . DS . 'notice.txt'))
                    echo '<div class="alert alert-warning" role="alert">' . file_get_contents(ROOT . DS . 'notice.txt') . '</div>';
                ?>
                <div class="well">
                    <?php if ($forbidden === true) { ?>

                        <h2>Upload forbidden</h2>

                        <p>Due to configured restrictions, you are not allowed to upload files at this time</p>

                    <?php } else { ?>
                        <div id="uploadinfo"></div>
                        <p>
                            Max Upload size: <?=(int)(ini_get('upload_max_filesize'))?>MB / File<br/>
                            Allowed file types: <?= implode(', ', getAllContentFiletypes()) ?>
                            <?php
                            if (defined('UPLOAD_CODE') && UPLOAD_CODE != ''):?>
                                <br>Upload Code: <input type="password" id="uploadcode"  />
                            <?php endif; ?>
                        </p>
                        <form class="dropzone well" id="dropzone" method="post" action="/api/upload" enctype="multipart/form-data">
                            <div class="fallback">
                                <input name="file" type="file" multiple />
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>


    </div>

    <div class="container">
        <h2 id="api" class="section-heading">Using PictShare</h2>

        <div class="row">
            <div class="col-6">
                <h3>Basics</h3>
                <p>
                    When you upload an image you'll get a link like this:<pre><code class="url"><?= getURL() ?>abcef123.jpg</code></pre>
                    You can modify the size of the image by adding a size parameter to the URL. For example this will render the image in 800x600:
                    <pre><code class="url"><?= getURL() ?>800x600/abcef123.jpg</code></pre>
                </p>

                <p>
                    If you want to force the size to really be 800x600, you can use the "forcesize" parameter. It will still keep the aspect ratio but zoom in as needed to fit the dimensions
                    <pre><code class="url"><?= getURL() ?>800x600/forcesize/abcef123.jpg</code></pre>
                </p>

                <p>There are many more of these <a target="_blank" href="https://github.com/HaschekSolutions/pictshare/blob/master/rtfm/MODIFIERS.md">modifiers</a> and even <a target="_blank" href="https://github.com/HaschekSolutions/pictshare/blob/master/rtfm/IMAGEFILTERS.md">filters</a> available</p>

            </div>
        </div>

        <hr class="no-print border border-primary border-2 opacity-50">
    </div>

    

    <div class="container">
        <h2 id="api" class="section-heading">Using the API</h2>
        <div class="row">
            <div class="col-6">
                <h2>Basics</h2>

                <p>
                    All API calls are done via GET or POST requests. The API will return JSON encoded data.
                </p>

                Base URL
                <pre><code class="url"><?= getURL() ?>api</code></pre>

            </div>

            <div class="col-6">
                <h2>Error handling</h2>
                When the status is err there will always be a field "reason" that explains what went wrong.
                <pre><code class="json">
{
  "status": "err",
  "reason": "File not a valid image"
}
                    </code></pre>

            </div>

            <div class="w-100">
                <hr />
            </div>

            <div class="col-6">
                <h2>Uploading an image</h2>

                API call
                <pre><code class="url">/upload</code></pre>

                <p>You can post a file using the POST variable <span class="badge text-bg-secondary">file</span></p>

                CURL example
                <pre><code class="bash">curl -s -F "file=@myphoto.jpg" "<?= getURL() ?>upload"</code></pre>

                Output
                <pre><code class="json">
{
  "status": "ok",
  "hash": "7eli4d.jpg",
  "filetype": "image/jpeg",
  "url": "http://localhost:8080/7eli4d.jpg",
  "delete_code": "jxgat3wze8lmn9sqwxy4x32p2xm7211g",
  "delete_url": "http://localhost:8080/delete_jxgat3wze8lmn9sqwxy4x32p2xm7211g/7eli4d.jpg"
}</code></pre>
            </div>
            <div class="col-6">
                <h2>Grabbing a URL</h2>

                API call
                <pre><code class="url">/upload?url=<span class="badge text-bg-secondary">url of image</span></code></pre>

                <p>You can specify a URL in the POST or GET variable <span class="badge text-bg-secondary">url</span>
                    that the call will try to download and process.</p>

                CURL example
                <pre><code class="bash">curl -s "<?= getURL() ?>api/upload?url=https://pictshare.net/d2j1e1.png</code></pre>

                Output
                <pre><code class="json">
{
  "status": "ok",
  "hash": "ysj455.webp",
  "filetype": "image/webp",
  "url": "http://localhost:8080/ysj455.webp",
  "delete_code": "4l0w04l4s42xddt2s5mrj1wikxz11l5z",
  "delete_url": "http://localhost:8080/delete_4l0w04l4s42xddt2s5mrj1wikxz11l5z/ysj455.webp"
}
</code></pre>
            </div>

            <div class="w-100">
                <hr />
            </div>

            <div class="col-6">
                <h2>Uploading Base64 encoded content</h2>
                <p>It's also possible to upload supported files via Base64 strings by providing the <span class="badge text-bg-secondary">base64</span> http parameter</p>

                API call
                <pre><code class="url">/upload/?base64=<span class="badge text-bg-secondary">sha1 hash of file</span></code></pre>

                CURL example
                <pre><code class="bash">(echo -n "base64="; echo -n "data:image/jpeg;base64,$(base64 -w 0 Screenshot3.jpg)") | curl --data @- <?= getURL() ?>api/upload</code></pre>

                Output
                <pre><code class="json">
{
  "status": "ok",
  "hash": "5e6alk.jpg",
  "filetype": "image/jpeg",
  "url": "http://localhost:8080/5e6alk.jpg",
  "delete_code": "7ha2b5ccvsuvdd3qdnegzb2zqa9zxb5t",
  "delete_url": "http://localhost:8080/delete_7ha2b5ccvsuvdd3qdnegzb2zqa9zxb5t/5e6alk.jpg"
}</code></pre>

            </div>
            <div class="col-6">
                
            </div>

        </div>
    </div>


    <div class="container">
        <footer>(c)<?php echo date("y"); ?> by<br /><a href="https://haschek.solutions" target="_blank"><img height="30" src="/css/imgs/hs_logo.png" /></a></footer>
    </div>


    <script src="/js/dropzone.js"></script>
    <script src="/js/highlight.pack.js"></script>
    <script src="/js/pictshare.js"></script>
</BODY>

</HTML>