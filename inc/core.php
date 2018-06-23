<?php

use PictShare\Classes\FilterFactory;

function getUserIP()
{
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if (strpos($forward, ',')) {
        $a = explode(',', $forward);
        $forward = trim($a[0]);
    }
    if (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } elseif (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } else {
        $ip = $remote;
    }
    return $ip;
}

function stripSlashesDeep($value)
{
    $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
    return $value;
}

function removeMagicQuotes()
{
    if (get_magic_quotes_gpc()) {
        $_GET    = stripSlashesDeep($_GET);
        $_POST   = stripSlashesDeep($_POST);
        $_COOKIE = stripSlashesDeep($_COOKIE);
    }
}

function callHook()
{
    global $url;

    whatToDo($url);
}

function whatToDo($url)
{
    $pm = new PictshareModel();

    $data = $pm->urlToData($url);
    $vars = null;

    if (!is_array($data) || !$data['hash']) {
        if ((UPLOAD_FORM_LOCATION && $url == UPLOAD_FORM_LOCATION) || (!UPLOAD_FORM_LOCATION)) {
            $upload_answer = $pm->processUploads();
            if ($upload_answer) {
                $o = $upload_answer;
            } else {
                $o = $pm->renderUploadForm();
            }

            $vars['content'] = $o;
            $vars['slogan'] = $pm->translate(2);
        }

        if (!$vars && LOW_PROFILE) {
            header('HTTP/1.0 404 Not Found');
            exit();
        }

        if (!$vars) {
            $vars['content'] = $pm->translate(12);
            $vars['slogan'] = $pm->translate(2);
        }

        render($vars);
    } elseif ($data['album']) {
        renderAlbum($data);
    } else {
        renderImage($data);
    }
}

function renderAlbum($data)
{
    $size    = 300;
    $filters = '';

    if ($data['filter']) {
        $filters = implode('/', $data['filter']) . '/';
    }

    if ($data['size']) {
        $size = $data['size'] . '/';
    } elseif (!$data['responsive']) {
        $size = '300x300/';
    }

    $forcesize = ($data['forcesize'] ? 'forcesize/' : '');

    $content = '';

    foreach ($data['album'] as $hash) {
        $content .= '<a href="' . PATH . $filters . $hash . '"><img class="picture" src="' . PATH . $size . $forcesize . $filters . $hash . '" /></a>';
    }

    if ($data['embed'] === true) {
        include ROOT . DS . 'template_album_embed.php';
    } else {
        include ROOT . DS . 'template_album.php';
    }
}

function renderImage($data)
{
    $hash = $data['hash'];
    if ($data['changecode']) {
        $changecode = $data['changecode'];
        unset($data['changecode']);
    }

    $pm = new PictshareModel();
    $base_path = ROOT . DS . 'upload' . DS . $hash . DS;
    $path = $base_path . $hash;
    $type = $pm->isTypeAllowed($pm->getTypeOfFile($path));
    $cached = false;

    //update last_rendered of this hash so we can later
    //sort out old, unused images easier
    @file_put_contents($base_path . 'last_rendered.txt', time());

    $cachename = $pm->getCacheName($data);
    $cachepath = $base_path . $cachename;
    if (file_exists($cachepath)) {
        $path = $cachepath;
        $cached = true;
    } elseif (MAX_RESIZED_IMAGES > -1 && $pm->countResizedImages($hash) > MAX_RESIZED_IMAGES) {
        // If the number of max resized images is reached, just show the real one.
        $path = ROOT . DS . 'upload' . DS . $hash . DS . $hash;
    }

    switch ($type) {
        case 'jpg':
            header('Content-type: image/jpeg');
            $im = imagecreatefromjpeg($path);
            if (!$cached) {
                if ($pm->changeCodeExists($changecode)) {
                    changeImage($im, $data);
                    imagejpeg($im, $cachepath, (defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
                }
            }
            imagejpeg($im);
            break;
        case 'png':
            header('Content-type: image/png');
            $im = imagecreatefrompng($path);
            if (!$cached) {
                if ($pm->changeCodeExists($changecode)) {
                    changeImage($im, $data);
                    imagepng($im, $cachepath, (defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                }
            }
            imagealphablending($im, true);
            imagesavealpha($im, true);
            imagepng($im);
            break;
        case 'gif':
            if ($data['mp4'] || $data['webm'] || $data['ogg']) { //user wants mp4 or webm or ogg
                $gifpath = $path;
                $mp4path = $base_path . 'mp4_1.' . $hash; //workaround.. find a better solution!
                $webmpath = $base_path . 'webm_1.' . $hash;
                $oggpath = $base_path . 'ogg_1.' . $hash;

                if (!file_exists($mp4path) && !$data['preview']) { //if mp4 does not exist, create it
                    $pm->gifToMP4($gifpath, $mp4path);
                }

                if (!file_exists($webmpath) && $data['webm'] && !$data['preview']) {
                    $pm->saveAsWebm($gifpath, $webmpath);
                }

                if (!file_exists($oggpath) && $data['ogg'] && !$data['preview']) {
                    $pm->saveAsOGG($gifpath, $oggpath);
                }

                if ($data['raw']) {
                    if ($data['webm']) {
                        serveFile($webmpath, $hash . '.webm', 'video/webm');
                    }
                    if ($data['ogg']) {
                        serveFile($oggpath, $hash . '.ogg', 'video/ogg');
                    } else {
                        serveMp4($mp4path, $hash . '.mp4', 'video/mp4');
                    }
                } elseif ($data['preview']) {
                    $file = $mp4path;
                    if (!file_exists($cachepath)) {
                        $pm->saveFirstFrameOfMP4($mp4path, $cachepath);
                    }
                    header('Content-type: image/jpeg');
                    readfile($cachepath);
                } else {
                    renderMP4($mp4path, $data);
                }
            } else { //user wants gif
                if (!$cached && $data['size']) {
                    $pm->resizeFFMPEG($data, $cachepath, 'gif');
                }
                header('Content-type: image/gif');
                if (file_exists($cachepath)) {
                    readfile($cachepath);
                } else {
                    readfile($path);
                }
            }

            break;
        case 'mp4':
            if (!$cached && !$data['preview']) {
                $pm->resizeFFMPEG($data, $cachepath, 'mp4');
                $path = $cachepath;
            }

            if (file_exists($cachepath) && filesize($cachepath) === 0) {
                // If there was an error and the file is 0 bytes, use the original.
                $cachepath = ROOT . DS . 'upload' . DS . $hash . DS . $hash;
            }

            if ($data['webm']) {
                $pm->saveAsWebm(ROOT . DS . 'upload' . DS . $hash . DS . $hash, $cachepath);
            }

            if ($data['ogg']) {
                $pm->saveAsOGG(ROOT . DS . 'upload' . DS . $hash . DS . $hash, $cachepath);
            }

            if ($data['raw']) {
                serveMP4($cachepath, $hash, 'video/mp4');
            } elseif ($data['preview']) {
                if (!file_exists($cachepath)) {
                    $pm->saveFirstFrameOfMP4($path, $cachepath);
                }
                header('Content-type: image/jpeg');
                readfile($cachepath);
            } else {
                renderMP4($path, $data);
            }
            break;
    }

    exit();
}

function rotate(&$im, $direction)
{
    switch ($direction) {
        case 'upside':
            $angle = 180;
            break;
        case 'left':
            $angle = 90;
            break;
        case 'right':
            $angle = -90;
            break;
        default:
            $angle = 0;
            break;
    }

    $im = FilterFactory::getFilter('rotate')->setSettings(['angle' => $angle])->apply()->getImage();
}

function forceResize(&$img, $size)
{
    $pm = new PictshareModel();

    $sd = $pm->sizeStringToWidthHeight($size);
    $maxWidth  = $sd['width'];
    $maxHeight = $sd['height'];

    $width = imagesx($img);
    $height = imagesy($img);

    $maxWidth = ($maxWidth > $width ? $width : $maxWidth);
    $maxHeight = ($maxHeight > $height ? $height : $maxHeight);


    $dstImg = imagecreatetruecolor($maxWidth, $maxHeight);
    $srcImg = $img;

    $palsize = imagecolorstotal($img);
    for ($i = 0; $i < $palsize; $i++) {
        $colors = imagecolorsforindex($img, $i);
        imagecolorallocate($dstImg, $colors['red'], $colors['green'], $colors['blue']);
    }

    imagefill($dstImg, 0, 0, IMG_COLOR_TRANSPARENT);
    imagesavealpha($dstImg, true);
    imagealphablending($dstImg, true);

    $newWidth = $height * $maxWidth / $maxHeight;
    $newHeight = $width * $maxHeight / $maxWidth;
    // If the new width is greater than the actual width of the image,
    // then the height is too large and the rest cut off, or vice versa.
    if ($newWidth > $width) {
        //cut point by height
        $hPoint = (($height - $newHeight) / 2);
        //copy image
        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, $hPoint, $maxWidth, $maxHeight, $width, $newHeight);
    } else {
        //cut point by width
        $wPoint = (($width - $newWidth) / 2);
        imagecopyresampled($dstImg, $srcImg, 0, 0, $wPoint, 0, $maxWidth, $maxHeight, $newWidth, $height);
    }

    $img = $dstImg;
}

/**
 * From: https://stackoverflow.com/questions/4590441/php-thumbnail-image-resizing-with-proportions
 *
 * @param $img
 * @param int $size
 */
function resize(&$img, $size)
{
    $pm = new PictshareModel();

    $sd = $pm->sizeStringToWidthHeight($size);
    $maxWidth  = $sd['width'];
    $maxHeight = $sd['height'];

    $width = imagesx($img);
    $height = imagesy($img);

    if (!ALLOW_BLOATING) {
        if ($maxWidth > $width) {
            $maxWidth = $width;
        }
        if ($maxHeight > $height) {
            $maxHeight = $height;
        }
    }

    if ($height > $width) {
        $ratio = $maxHeight / $height;
        $newHeight = $maxHeight;
        $newWidth = $width * $ratio;
    } else {
        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = $height * $ratio;
    }

    $newImg = imagecreatetruecolor($newWidth, $newHeight);

    $palSize = imagecolorstotal($img);
    for ($i = 0; $i < $palSize; $i++) {
        $colors = imagecolorsforindex($img, $i);
        imagecolorallocate($newImg, $colors['red'], $colors['green'], $colors['blue']);
    }

    imagefill($newImg, 0, 0, IMG_COLOR_TRANSPARENT);
    imagesavealpha($newImg, true);
    imagealphablending($newImg, true);

    imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $img = $newImg;
}

/**
 * @param $im
 * @param array $vars
 *
 * @return resource GD image resource
 */
function filter(&$im, $vars)
{
    foreach ($vars as $var) {
        $filterName  = $var;
        $filterValue = null;
        $params      = [];

        if (strpos($var, '_')) {
            list($filterName, $filterValue) = explode('_', $var);
        }

        if ($filterValue !== null) {
            $params = [
                'brightness' => $filterValue,
                'blur'       => $filterValue,
                'pixelate'   => $filterValue,
                'smooth'     => $filterValue,
                'angle'      => $filterValue,
            ];
        }

        $im = FilterFactory::getFilter($filterName)
            ->setImage($im)
            ->setSettings($params)
            ->apply()
            ->getImage();
    }

    return $im;
}

function changeImage(&$im, array $data)
{
    foreach ($data as $action => $val) {
        switch ($action) {
            case 'rotate':
                rotate($im, $val);
                break;
            case 'size':
                $data['forcesize'] === true ? forceResize($im, $val) : resize($im, $val);
                break;
            case 'filter':
                filter($im, $val);
                break;
        }
    }
}

function render($variables = null)
{
    if (is_array($variables)) {
        extract($variables, EXTR_OVERWRITE);
    }
    include ROOT . DS . 'template.php';
}

function renderMP4($path, $data)
{
    $pm = new PictshareModel();
    $hash = $data['hash'];
    $urldata = $pm->getURLInfo($path, true);
    if ($data['size']) {
        $hash = $data['size'] . '/' . $hash;
    }
    $info = $pm->getSizeOfMP4($path);
    $width = $info['width'];
    $height = $info['height'];
    $filesize = $urldata['humansize'];
    include ROOT . DS . 'template_mp4.php';
}

//
// from: https://stackoverflow.com/questions/25975943/php-serve-mp4-chrome-provisional-headers-are-shown-request-is-not-finished-ye
//
function serveFile($filename, $filename_output = false, $mime = 'application/octet-stream')
{
    $buffer_size = 8192;
    $expiry = 90; //days

    if (!file_exists($filename)) {
        throw new Exception('File not found: ' . $filename);
    }
    if (!is_readable($filename)) {
        throw new Exception('File not readable: ' . $filename);
    }

    header_remove('Cache-Control');
    header_remove('Pragma');

    $byte_offset = 0;
    $filesize_bytes = $filesize_original = filesize($filename);

    header('Accept-Ranges: bytes', true);
    header('Content-Type: ' . $mime, true);

    /*
        if($filename_output)
        {
            header('Content-Disposition: attachment; filename="' . $filename_output . '"');
        }
    */
    header('Content-Disposition: inline;');
    // Content-Range header for byte offsets
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('%bytes=(\d+)-(\d+)?%i', $_SERVER['HTTP_RANGE'], $match)) {
        $byte_offset = (int) $match[1];//Offset signifies where we should begin to read the file
        if (isset($match[2])) {//Length is for how long we should read the file according to the browser, and can never go beyond the file size
            $filesize_bytes = min((int) $match[2], $filesize_bytes - $byte_offset);
        }
        header('HTTP/1.1 206 Partial content');
        header(sprintf('Content-Range: bytes %d-%d/%d', $byte_offset, $filesize_bytes - 1, $filesize_original)); // Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent
    }

    $byte_range = $filesize_bytes - $byte_offset;

    header('Content-Length: ' . $byte_range);
    header('Expires: ' . (new \DateTime())->modify('+' . $expiry . ' days')->format('D, d M Y H:i:s') . ' GMT');

    $bytes_remaining = $byte_range;

    $handle = fopen($filename, 'rb');
    if (!$handle) {
        throw new Exception('Could not get handle for file: ' .  $filename);
    }
    if (fseek($handle, $byte_offset, SEEK_SET) == -1) {
        throw new Exception('Could not seek to byte offset %d', $byte_offset);
    }

    while ($bytes_remaining > 0) {
        $chunksize_requested = min($buffer_size, $bytes_remaining);
        $buffer = fread($handle, $chunksize_requested);
        $chunksize_real = strlen($buffer);
        if ($chunksize_real == 0) {
            break;
        }
        $bytes_remaining -= $chunksize_real;
        echo $buffer;
        flush();
    }
}


//via gist: https://gist.github.com/codler/3906826
function serveMP4($path, $hash, $null)
{
    if ($fp = fopen($path, 'rb')) {
        $size = filesize($path);
        $length = $size;
        $start = 0;
        $end = $size - 1;
        header('Content-type: video/mp4');
        header("Accept-Ranges: 0-$length");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        header("Content-Range: bytes $start-$end/$size");
        header('Content-Length: ' . $length);
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
        }
        fclose($fp);
        exit();
    }

    die('file not found');
}

/**
 * @param string $ip
 * @param string $range
 *
 * @return bool
 */
function cidrMatch(string $ip, string $range): bool
{
    list($subnet, $bits) = explode('/', $range);

    $ipInt  = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask   = -1 << (32 - $bits);
    $subnet &= $mask; // Nb: in case the supplied subnet wasn't correctly aligned.

    return ($ipInt & $mask) === $subnet;
}

/**
 * @param string $ip
 *
 * @return bool
 */
function isIP(string $ip): bool
{
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * @param int $length
 * @param string $keySpace
 *
 * @return string
 */
function getRandomString(int $length = 32, string $keySpace = '0123456789abcdefghijklmnopqrstuvwxyz'): string
{
    $str = '';
    $max = mb_strlen($keySpace, '8bit') - 1;

    for ($i = 0; $i < $length; ++$i) {
        $str .= $keySpace[random_int(0, $max)];
    }

    return $str;
}

/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function startsWith(string $haystack, string $needle): bool
{
    return strpos($haystack, $needle) === 0;
}
