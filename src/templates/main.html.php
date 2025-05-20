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
            Max Upload size: <?= (int)(ini_get('upload_max_filesize')) ?>MB / File<br />
            Allowed file types: <?= implode(', ', getAllContentFiletypes()) ?>
            <?php
            if (defined('UPLOAD_CODE') && UPLOAD_CODE != ''): ?>
                <br>Upload Code: <input type="password" id="uploadcode" />
            <?php endif; ?>
        </p>
        <form class="dropzone well" id="dropzone" method="post" action="/api/upload" enctype="multipart/form-data">
            <div class="fallback">
                <input name="file" type="file" multiple />
            </div>
        </form>
    <?php } ?>
</div>


<h2 id="api" class="section-heading">Using PictShare</h2>

<div class="row">
    <div class="col-6">
        <h3>Basics</h3>
        <p>
            When you upload an image you'll get a link like this:
        <pre><code class="url"><?= getURL() ?>abcef123.jpg</code></pre>
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
        <pre><code class="url">/upload/?base64=<span class="badge text-bg-secondary">base64 encoded string</span></code></pre>

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