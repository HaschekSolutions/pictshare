<?php

namespace App\Views;

use App\Config\ConfigInterface;
use App\Models\PictshareModel;
use App\Support\File;
use App\Support\MIMEType;
use App\Support\Translator;
use App\Transformers\Image as ImageTransformer;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

/**
 * Class View
 * @package App\Support
 */
class View
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var PictshareModel
     */
    protected $pictshareModel;

    /**
     * @var ImageTransformer
     */
    protected $imageTransformer;

    /**
     * @var Mustache_Engine
     */
    protected $mustache;

    /**
     * View constructor.
     *
     * @param ConfigInterface  $config
     * @param PictshareModel   $pictshareModel
     * @param ImageTransformer $imageTransformer
     * @param Mustache_Engine  $mustache
     */
    public function __construct(
        ConfigInterface $config,
        PictshareModel $pictshareModel,
        ImageTransformer $imageTransformer,
        Mustache_Engine $mustache
    ) {
        $this->config           = $config;
        $this->pictshareModel   = $pictshareModel;
        $this->imageTransformer = $imageTransformer;
        $this->mustache         = $mustache;

        $this->mustache->setLoader(new Mustache_Loader_FilesystemLoader(__DIR__ . '/../../resources/templates'));
    }

    /**
     * @param array $variables
     *
     * @return void
     */
    public function render($variables = null)
    {
        $tpl = $this->mustache->loadTemplate('template');
        echo $tpl->render([
            'title'   => $this->config->get('app.title'),
            'path'    => relative_path(),
            'year'    => date("Y"),
            'slogan'  => isset($variables['slogan']) ? $variables['slogan'] : '',
            'content' => isset($variables['content']) ? $variables['content'] : ''
        ]);
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
        } elseif (!isset($data['responsive']) || !$data['responsive']) {
            $size = '300x300/';
        }

        foreach ($data['album'] as $hash) {
            $content .= '<a href="' . relative_path($filters . $hash) . '">
                            <img class="picture" src="' . relative_path($size . $forcesize . $filters . $hash) . '" />
                        </a>';
        }

        if ($data['embed'] === true) {
            $tpl = $this->mustache->loadTemplate('template_album_embed');
        } else {
            $tpl = $this->mustache->loadTemplate('template_album');
        }

        echo $tpl->render([
            'title'      => $this->config->get('app.title'),
            'path'       => relative_path(),
            'year'       => date("Y"),
            'slogan'     => isset($data['slogan']) ? $data['slogan'] : '',
            'content'    => $content,
            'responsive' => isset($data['responsive']) ? $data['responsive'] : false
        ]);
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function renderFile($data)
    {
        $changecode = '';
        if (isset($data['changecode']) && $data['changecode']) {
            $changecode = $data['changecode'];
            unset($data['changecode']);
        }

        $hash    = $data['hash'];
        $subdir  = $data['subdir'];
        $hashdir = $subdir . '/' . $hash;

        $base_path = File::uploadDir($hashdir . '/');
        $full_path = $base_path . $hash;

        // update last_rendered of this hash so we can later
        // sort out old, unused images easier
        @file_put_contents($base_path . 'last_rendered.txt', time());

        $cached           = false;
        $cachename        = $this->pictshareModel->getCacheName($data);
        $cachepath        = $base_path . $cachename;
        $maxResizedImages = $this->config->get('app.max_resized_images', 20);

        if (file_exists($cachepath)) {
            $full_path = $cachepath;
            $cached    = true;
        } elseif ($maxResizedImages > -1 && $this->pictshareModel->countResizedImages($hashdir) > $maxResizedImages) {
            // if the number of max resized images is reached, just show the real one
        }

        $type = $this->pictshareModel->isTypeAllowed($this->pictshareModel->getTypeOfFile($full_path));

        switch ($type) {
            case 'jpg':
                header("Content-type: image/jpeg");
                $im = imagecreatefromjpeg($full_path);
                if (!$cached) {
                    if ($this->pictshareModel->changeCodeExists($changecode)) {
                        $this->imageTransformer->transform($im, $data);
                        imagejpeg(
                            $im,
                            $cachepath,
                            ($this->config !== null ? $this->config->get('app.jpeg_compression') : 90)
                        );
                    }
                }
                imagejpeg($im);
                break;

            case 'png':
                header("Content-type: image/png");
                $im = imagecreatefrompng($full_path);
                if (!$cached) {
                    if ($this->pictshareModel->changeCodeExists($changecode)) {
                        $this->imageTransformer->transform($im, $data);
                        imagepng(
                            $im,
                            $cachepath,
                            ($this->config !== null ? $this->config->get('app.png_compression') : 6)
                        );
                    }
                }
                imageAlphaBlending($im, true);
                imageSaveAlpha($im, true);
                imagepng($im);
                break;

            case 'gif':
                if ($data['mp4'] || $data['webm'] || $data['ogg']) { //user wants mp4 or webm or ogg
                    $gifpath  = $full_path;
                    $mp4path  = $base_path . 'mp4_1.' . $hash; //workaround.. find a better solution!
                    $webmpath = $base_path . 'webm_1.' . $hash;
                    $oggpath  = $base_path . 'ogg_1.' . $hash;

                    if (!file_exists($mp4path) && !$data['preview']) { //if mp4 does not exist, create it
                        $this->pictshareModel->gifToMP4($gifpath, $mp4path);
                    }

                    if (!file_exists($webmpath) && $data['webm'] && !$data['preview']) {
                        $this->pictshareModel->saveAsWebm($gifpath, $webmpath);
                    }

                    if (!file_exists($oggpath) && $data['ogg'] && !$data['preview']) {
                        $this->pictshareModel->saveAsOGG($gifpath, $oggpath);
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
                            //$file = $mp4path;
                            if (!file_exists($cachepath)) {
                                $this->pictshareModel->saveFirstFrameOfMP4($mp4path, $cachepath);
                            }
                            header("Content-type: image/jpeg");
                            readfile($cachepath);
                        } else {
                            $this->renderMP4($mp4path, $data);
                        }
                    }
                } else { //user wants gif
                    if (!$cached && $data['size']) {
                        $this->imageTransformer->resizeFFMPEG($data, $cachepath, 'gif');
                    }
                    header("Content-type: image/gif");
                    if (file_exists($cachepath)) {
                        readfile($cachepath);
                    } else {
                        readfile($full_path);
                    }
                }

                break;

            case 'mp4':
                if (!$cached && !$data['preview']) {
                    $this->imageTransformer->resizeFFMPEG($data, $cachepath, 'mp4');
                    $full_path = $cachepath;
                }

                if (file_exists($cachepath) && filesize($cachepath) == 0) {
                    // if there was an error and the file is 0 bytes, use the original
                    $cachepath = File::uploadDir($hashdir . '/' . $hash);
                }

                if ($data['webm']) {
                    $this->pictshareModel->saveAsWebm(File::uploadDir($hashdir . '/' . $hash), $cachepath);
                }

                if ($data['ogg']) {
                    $this->pictshareModel->saveAsOGG(File::uploadDir($hashdir . '/' . $hash), $cachepath);
                }

                if ($data['raw']) {
                    $this->serveMP4($cachepath, $hash, 'video/mp4');
                } elseif ($data['preview']) {
                    if (!file_exists($cachepath)) {
                        $this->pictshareModel->saveFirstFrameOfMP4($full_path, $cachepath);
                    }
                    header("Content-type: image/jpeg");
                    readfile($cachepath);
                } else {
                    $this->renderMP4($full_path, $data);
                }
                break;

            default:
                $fileMIMEType = MIMEType::getMimeTypeFromExtension($type);

                header('Content-Description: File Transfer');
                header("Content-Type: " . $fileMIMEType);
                header('Content-Disposition: attachment; filename=' . $hash);
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($full_path));
                readfile($full_path);
                break;
        }

        exit();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function renderError($data)
    {
        echo $data['error_message'];
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
        $hash    = isset($data['hash']) ? $data['hash'] : '';
        $subdir  = isset($data['subdir']) ? $data['subdir'] : '';
        $urldata = $this->pictshareModel->getURLInfo($path, true);
        if ($data['size']) {
            $hash = $data['size'] . '/' . $hash;
        }
        $info     = $this->pictshareModel->getSizeOfMP4($path);
        $width    = $info['width'];
        $height   = $info['height'];
        $filesize = $urldata['humansize'];

        $tpl = $this->mustache->loadTemplate('template_mp4');
        echo $tpl->render([
            'title'      => $this->config->get('app.title'),
            'path'       => relative_path(),
            'domain'     => domain_path(),
            'rawurlpath' => rawurlencode(domain_path(relative_path($subdir . '/' . $hash))),
            'year'       => date("Y"),
            'hash'       => $hash,
            'width'      => $width,
            'height'     => $height,
            'filesize'   => $filesize
        ]);
    }

    /**
     * @return string
     */
    public function renderUploadForm()
    {
        if (!$this->config->get('app.uploadform_enable')) {
            return 'This is not the page you are looking for!';
        }

        $maxfilesize = (int) (ini_get('upload_max_filesize'));

        $upload_code_form = '';
        if ($this->config->get('app.upload_code', false)) {
            $upload_code_form = '<strong>' . Translator::translate(20) .
                                ': </strong><input class="input" type="password" name="upload_code" value="' .
                                $_REQUEST['upload_code'] . '"><div class="clear"></div>';
        }

        return '
        <div class="clear"></div>
        <strong>' . Translator::translate(0) . ': ' . $maxfilesize . 'MB / File</strong><br>
        <strong>' . Translator::translate(1) . '</strong>
        <br><br>
        <form id="form" enctype="multipart/form-data" method="post">
            <div id="formular">
                ' . $upload_code_form . '
                <strong>' . Translator::translate(4) . ': </strong>
                <input class="input" type="file" name="pic[]" multiple>
                <div class="clear"></div>
                <div class="clear"></div><br>
            </div>
            <input class="btn" style="font-size:15px;font-weight:bold;background-color:#74BDDE;padding:3px;" 
                   type="submit" id="submit" name="submit" value="' . Translator::translate(3) . '" 
                   onClick="setTimeout(function() {
                                document.getElementById(\'submit\').disabled = \'disabled\';
                            }, 1);
                            $(\'#movingBallG\').fadeIn()">
            <div id="movingBallG" class="invisible">
                <div class="movingBallLineG"></div>
                <div id="movingBallG_1" class="movingBallG"></div>
            </div>
        </form>';
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

        //$buffer          = '';
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
                //$c_start = $start;
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
