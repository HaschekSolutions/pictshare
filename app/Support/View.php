<?php

namespace App\Support;

use App\Models\PictshareModel;
use App\Transformers\Image;

/**
 * Class View
 * @package App\Support
 */
class View
{
    /**
     * @param array $variables
     *
     * @return void
     */
    public function render($variables = null)
    {
        if (is_array($variables)) {
            extract($variables);
        }
        include(ROOT . DS . 'resources' . DS . 'templates' . DS . 'template.php');
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function renderAlbum($data)
    {
        $content   = '';
        $filters   = '';
        $size      = '';
        $forcesize = isset($data['forcesize']) && $data['forcesize'] ? 'forcesize/' : '';

        if (isset($data['filter']) && $data['filter']) {
            $filters = implode('/', $data['filter']) . '/';
        }

        if (isset($data['size']) && $data['size']) {
            $size = $data['size'] . '/';
        } else {
            if (!isset($data['responsive']) || !$data['responsive']) {
                $size = '300x300/';
            }
        }

        foreach ($data['album'] as $hash) {
            $content .= '<a href="' . PATH . $filters . $hash . '">
                            <img class="picture" src="' . PATH . $size . $forcesize . $filters . $hash . '" />
                        </a>';
        }

        if ($data['embed'] === true) {
            include(ROOT . DS . 'resources' . DS . 'templates' . DS . 'template_album_embed.php');
        } else {
            include(ROOT . DS . 'resources' . DS . 'templates' . DS . 'template_album.php');
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function renderImage($data)
    {
        $hash = isset($data['hash']) ? $data['hash'] : '';

        $changecode = '';
        if (isset($data['changecode']) && $data['changecode']) {
            $changecode = $data['changecode'];
            unset($data['changecode']);
        }

        $pm             = new PictshareModel();
        $imgTransformer = new Image();
        $base_path      = ROOT . DS . 'upload' . DS . $hash . DS;
        $path           = $base_path . $hash;
        $type           = $pm->isTypeAllowed($pm->getTypeOfFile($path));
        $cached         = false;

        //update last_rendered of this hash so we can later
        //sort out old, unused images easier
        @file_put_contents($base_path . 'last_rendered.txt', time());

        $cachename = $pm->getCacheName($data);
        $cachepath = $base_path . $cachename;
        if (file_exists($cachepath)) {
            $path   = $cachepath;
            $cached = true;
        } else {
            // if the number of max resized images is reached, just show the real one
            if (MAX_RESIZED_IMAGES > -1 && $pm->countResizedImages($hash) > MAX_RESIZED_IMAGES) {
                $path = ROOT . DS . 'upload' . DS . $hash . DS . $hash;
            }
        }

        switch ($type) {
            case 'jpg':
                header("Content-type: image/jpeg");
                $im = imagecreatefromjpeg($path);
                if (!$cached) {
                    if ($pm->changeCodeExists($changecode)) {
                        $imgTransformer->transform($im, $data);
                        imagejpeg($im, $cachepath, (defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
                    }
                }
                imagejpeg($im);
                break;

            case 'png':
                header("Content-type: image/png");
                $im = imagecreatefrompng($path);
                if (!$cached) {
                    if ($pm->changeCodeExists($changecode)) {
                        $imgTransformer->transform($im, $data);
                        imagepng($im, $cachepath, (defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                    }
                }
                imageAlphaBlending($im, true);
                imageSaveAlpha($im, true);
                imagepng($im);
                break;

            case 'gif':
                if ($data['mp4'] || $data['webm'] || $data['ogg']) { //user wants mp4 or webm or ogg
                    $gifpath  = $path;
                    $mp4path  = $base_path . 'mp4_1.' . $hash; //workaround.. find a better solution!
                    $webmpath = $base_path . 'webm_1.' . $hash;
                    $oggpath  = $base_path . 'ogg_1.' . $hash;

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
                            $this->serveFile($webmpath, $hash . '.webm', 'video/webm');
                        }
                        if ($data['ogg']) {
                            $this->serveFile($oggpath, $hash . '.ogg', 'video/ogg');
                        } else {
                            $this->serveMp4($mp4path, $hash . '.mp4', 'video/mp4');
                        }
                    } else {
                        if ($data['preview']) {
                            $file = $mp4path;
                            if (!file_exists($cachepath)) {
                                $pm->saveFirstFrameOfMP4($mp4path, $cachepath);
                            }
                            header("Content-type: image/jpeg");
                            readfile($cachepath);
                        } else {
                            $this->renderMP4($mp4path, $data);
                        }
                    }
                } else { //user wants gif
                    if (!$cached && $data['size']) {
                        $pm->resizeFFMPEG($data, $cachepath, 'gif');
                    }
                    header("Content-type: image/gif");
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

                if (file_exists($cachepath) &&
                    filesize($cachepath) == 0) { //if there was an error and the file is 0 bytes, use the original
                    $cachepath = ROOT . DS . 'upload' . DS . $hash . DS . $hash;
                }

                if ($data['webm']) {
                    $pm->saveAsWebm(ROOT . DS . 'upload' . DS . $hash . DS . $hash, $cachepath);
                }

                if ($data['ogg']) {
                    $pm->saveAsOGG(ROOT . DS . 'upload' . DS . $hash . DS . $hash, $cachepath);
                }

                if ($data['raw']) {
                    $this->serveMP4($cachepath, $hash, 'video/mp4');
                } else {
                    if ($data['preview']) {
                        if (!file_exists($cachepath)) {
                            $pm->saveFirstFrameOfMP4($path, $cachepath);
                        }
                        header("Content-type: image/jpeg");
                        readfile($cachepath);
                    } else {
                        $this->renderMP4($path, $data);
                    }
                }
                break;
        }

        exit();
    }

    /**
     * @param string $path
     * @param array $data
     *
     * @return void
     */
    public function renderMP4($path, $data)
    {
        // TODO: below
        $pm      = new PictshareModel;
        $hash    = isset($data['hash']) ? $data['hash'] : '';
        $urldata = $pm->getURLInfo($path, true);
        if ($data['size']) {
            $hash = $data['size'] . '/' . $hash;
        }
        $info     = $pm->getSizeOfMP4($path);
        $width    = $info['width'];
        $height   = $info['height'];
        $filesize = $urldata['humansize'];
        include(ROOT . DS . 'resources' . DS . 'templates' . DS . 'template_mp4.php');
    }


    /**
     * @see https://stackoverflow.com/questions/25975943/php-serve-mp4-chrome-provisional-headers-are-shown-request-is-not-finished-ye
     *
     * @param string      $filename
     * @param string|bool $filename_output
     * @param string      $mime
     *
     * @return void
     * @throws \Exception
     */
    public function serveFile($filename, $filename_output = false, $mime = 'application/octet-stream')
    {
        $buffer_size = 8192;
        $expiry      = 90; //days

        if (!file_exists($filename)) {
            throw new \Exception('File not found: ' . $filename);
        }
        if (!is_readable($filename)) {
            throw new \Exception('File not readable: ' . $filename);
        }

        header_remove('Cache-Control');
        header_remove('Pragma');

        $byte_offset    = 0;
        $filesize_bytes = $filesize_original = filesize($filename);

        header('Accept-Ranges: bytes', true);
        header('Content-Type: ' . $mime, true);

        /*
            if($filename_output)
            {
                header('Content-Disposition: attachment; filename="' . $filename_output . '"');
            }
        */
        header("Content-Disposition: inline;");
        // Content-Range header for byte offsets
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('%bytes=(\d+)-(\d+)?%i', $_SERVER['HTTP_RANGE'], $match)) {
            // Offset signifies where we should begin to read the file
            $byte_offset = (int) $match[1];
            // Length is for how long we should read the file according
            // to the browser, and can never go beyond the file size
            if (isset($match[2])) {
                $filesize_bytes = min((int) $match[2], $filesize_bytes - $byte_offset);
            }
            header("HTTP/1.1 206 Partial content");
            header(
                sprintf('Content-Range: bytes %d-%d/%d', $byte_offset, $filesize_bytes - 1, $filesize_original)
            ); ### Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent
        }

        $byte_range = $filesize_bytes - $byte_offset;

        header('Content-Length: ' . $byte_range);
        header('Expires: ' . date('D, d M Y H:i:s', time() + 60 * 60 * 24 * $expiry) . ' GMT');

        $buffer          = '';
        $bytes_remaining = $byte_range;

        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new \Exception("Could not get handle for file: " . $filename);
        }
        if (fseek($handle, $byte_offset, SEEK_SET) == -1) {
            throw new \Exception("Could not seek to byte offset %d", $byte_offset);
        }

        while ($bytes_remaining > 0) {
            $chunksize_requested = min($buffer_size, $bytes_remaining);
            $buffer              = fread($handle, $chunksize_requested);
            $chunksize_real      = strlen($buffer);
            if ($chunksize_real == 0) {
                break;
            }
            $bytes_remaining -= $chunksize_real;
            echo $buffer;
            flush();
        }
    }


    /**
     * @see (gist) https://gist.github.com/codler/3906826
     *
     * @param string $path
     * @param string $hash
     * @param mixed  $null
     *
     * @return void
     */
    public function serveMP4($path, $hash, $null)
    {
        if ($fp = fopen($path, "rb")) {
            $size   = filesize($path);
            $length = $size;
            $start  = 0;
            $end    = $size - 1;
            header('Content-type: video/mp4');
            header("Accept-Ranges: 0-$length");
            if (isset($_SERVER['HTTP_RANGE'])) {
                $c_start = $start;
                $c_end   = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                if (strpos($range, ',') !== false) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                if ($range == '-') {
                    $c_start = $size - substr($range, 1);
                } else {
                    $range   = explode('-', $range);
                    $c_start = $range[0];
                    $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }
                $c_end = ($c_end > $end) ? $end : $c_end;
                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }
                $start  = $c_start;
                $end    = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
            }
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: " . $length);
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
        } else {
            die('file not found');
        }
    }
}
