<?php

namespace App\Models;

use App\Config\ConfigInterface;
use App\Support\File;
use App\Support\MIMEType;
use App\Support\Str;
use App\Support\Translator;
use App\Support\Utils;
use App\Transformers\Image as ImageTransformer;

/**
 * Class PictshareModel
 * @package App\Models
 *
 * @todo This is not a model, this is a God class!
 */
class PictshareModel
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ImageTransformer
     */
    protected $imageTransformer;

    /**
     * Model constructor.
     *
     * @param ConfigInterface  $config
     * @param ImageTransformer $imageTransformer
     */
    public function __construct(ConfigInterface $config, ImageTransformer $imageTransformer)
    {
        $this->config           = $config;
        $this->imageTransformer = $imageTransformer;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function backend($params)
    {
        switch ($params[0]) {
            case 'mp4convert':
                $hash   = $params[1];
                $path   = $params[2];
                $source = $path . $hash;
                if (!File::isFile($hash)) {
                    exit('[x] Hash not found' . "\n");
                }
                echo "[i] Converting $hash to mp4\n";
                $this->saveAsMP4($source, $path . 'mp4_1.' . $hash);
                $this->saveAsMP4($source, $path . 'ogg_1.' . $hash);
                break;
        }

        return ['status' => 'ok'];
    }

    /**
     * @param string $source
     * @param string $target
     */
    public function saveAsMP4($source, $target)
    {
        $bin    = escapeshellcmd(root_path('bin/ffmpeg'));
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $h265   = "$bin -y -i $source -an -c:v libx264 -qp 0 -f mp4 $target";
        system($h265);
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    public function renderLegacyResized($path)
    {
        $a = explode('_', $path);
        if (count($a) != 2) {
            return false;
        }

        $hash = $a[1];
        $size = $a[0];

        if (!File::hashExists($hash)) {
            return false;
        }

        if (function_exists('renderResizedImage')) {
            return renderResizedImage($size, $hash);
        } else {
            return false;
        }
    }

    /**
     * @param string $hashdir
     *
     * @return int
     */
    public function countResizedImages($hashdir)
    {
        $fi = new \FilesystemIterator(File::uploadDir($hashdir . '/'), \FilesystemIterator::SKIP_DOTS);
        return iterator_count($fi);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function changeCodeExists($code)
    {
        $imageChangeCode = $this->config->get('app.image_change_code', false);
        if (!$imageChangeCode) {
            return true;
        }
        if (strpos($imageChangeCode, ';')) {
            $codes = explode(';', $imageChangeCode);
            foreach ($codes as $ucode) {
                if ($code == $ucode) {
                    return true;
                }
            }
        }

        if ($code == $imageChangeCode) {
            return true;
        }

        return false;
    }

    /**
     * @param $name
     *
     * @return array
     */
    public function processSingleUpload($name)
    {
        if ($this->config->get('app.upload_code', false) && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            exit(json_encode(['status' => 'ERR', 'reason' => Translator::translate(21)]));
        }

        //$im = $this->imageTransformer;
        $o  = [];

        if ($_FILES[$name]["error"] == UPLOAD_ERR_OK) {
            $type = $this->getTypeOfFile($_FILES[$name]["tmp_name"], $_FILES[$name]['name'], $_FILES[$name]['type']);
            $type = $this->isTypeAllowed($type);
            if (!$type) {
                exit(json_encode(['status' => 'ERR', 'reason' => 'Unsupported type']));
            }

            $data = $this->uploadFileFromURL($_FILES[$name]["tmp_name"], $type);
            if ($data['status'] === 'OK') {
                $hash = $data['hash'];
                $o    = [
                    'status' => 'OK',
                    'type'   => $type,
                    'hash'   => $hash,
                    'url'    => domain_path($hash),
                    'domain' => domain_path()
                ];
                if ($data['deletecode']) {
                    $o['deletecode'] = $data['deletecode'];
                }

                return $o;
            } elseif ($data['status'] === 'ERR') {
                return $data;
            }
        }


        return $o;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function uploadCodeExists($code)
    {
        $uploadCode = $this->config->get('app.upload_code', false);
        if (strpos($uploadCode, ';')) {
            $codes = explode(';', $uploadCode);
            foreach ($codes as $ucode) {
                if ($code == $ucode) {
                    return true;
                }
            }
        }

        if ($code == $uploadCode) {
            return true;
        }

        return false;
    }

    /**
     * @param string      $url
     * @param string|null $filename
     * @param string|null $mimeType
     *
     * @return bool|string
     */
    public function getTypeOfFile($url, $filename = null, $mimeType = null)
    {
        $fi   = new \finfo(FILEINFO_MIME);
        $type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));

        // some files (like .docx) are actually zip files, but we still want to
        // save them with proper extension so we need to calculate it somehow
        if (strpos($type, 'application/zip') !== false) {
            $typeTmp = null;

            // we first try to extract the "type" from the MIME type (if available)
            if ($mimeType !== null) {
                $typeFromMime = MIMEType::getExtensionFromMimeType($mimeType);
                if ($typeFromMime !== null) {
                    $typeTmp = $typeFromMime;
                }
            }

            // if MIME type is no available or didn't work, we try with filenames
            if ($typeTmp === null) {
                if ($filename !== null) {
                    $extension = File::getExtension($filename);
                } else {
                    $extension = File::getExtension($url);
                }
                if (MIMEType::isValidExtension($extension)) {
                    $typeTmp = substr($extension, 1);
                }
            }

            if ($typeTmp !== null) {
                // this is so it properly works with splitting below
                $type = 'type/' . $typeTmp;
            }
        }

        // to catch a strange error for PHP7 and Alpine Linux
        // if the file seems to be a stream, use unix file command
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' &&
            Str::startsWith($type, 'application/octet-stream')
        ) {
            $content_type = exec("file -bi " . escapeshellarg($url));
            if ($content_type && $content_type != $type && strpos($content_type, '/') !== false &&
                strpos($content_type, ';') !== false) {
                $type = $content_type;
            }
        }

        $arr = explode(';', trim($type));
        if (count($arr) > 1) {
            $a2   = explode('/', $arr[0]);
            $type = $a2[1];
        } else {
            $a2   = explode('/', $type);
            $type = $a2[1];
        }

        if ($type == 'octet-stream' && $this->isProperMP4($url)) {
            return 'mp4';
        }
        if ($type == 'mp4' && !$this->isProperMP4($url)) {
            return false;
        }


        return $type;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function isProperMP4($filename)
    {
        $file = escapeshellarg($filename);
        $tmp  = root_path('tmp/' . md5(time() + rand(1, 10000)) . '.' . rand(1, 10000) . '.log');
        $bin  = escapeshellcmd(root_path('/bin/ffmpeg'));

        $cmd = "$bin -i $file > $tmp 2>> $tmp";

        system($cmd);

        $answer = file($tmp);
        unlink($tmp);
        $ismp4 = false;
        if (is_array($answer)) {
            foreach ($answer as $line) {
                $line = trim($line);
                if (strpos($line, 'Duration: 00:00:00')) {
                    return false;
                }
                if (strpos($line, 'Video: h264')) {
                    $ismp4 = true;
                }
            }
        }

        return $ismp4;
    }

    /**
     * @param string $type
     *
     * @return bool|string
     */
    public function isTypeAllowed($type)
    {
        switch ($type) {
            case 'image/png':
                return 'png';
            case 'image/x-png':
                return 'png';
            case 'x-png':
                return 'png';
            case 'png':
                return 'png';

            case 'image/jpeg':
                return 'jpg';
            case 'jpeg':
                return 'jpg';
            case 'pjpeg':
                return 'jpg';

            case 'image/gif':
                return 'gif';
            case 'gif':
                return 'gif';

            case 'mp4':
                return 'mp4';

            default:
                $additionalTypes = $this->config->get('app.additional_file_types');
                if ($additionalTypes !== false) {
                    $additionalTypes = explode(',', $additionalTypes);

                    foreach ($additionalTypes as $additionalType) {
                        if ($type === $additionalType) {
                            return $type;
                        }
                    }
                }
                return false;
        }
    }

    /**
     * @param string           $url
     * @param null|string|bool $type
     *
     * @return array
     */
    public function uploadFileFromURL($url, $type = null)
    {
        if ($type === null) {
            $type = $this->getTypeOfFile($url);
            $type = $this->isTypeAllowed($type);
        }

        if (!$type) {
            return ['status' => 'ERR', 'reason' => 'wrong filetype'];
        }

        $filename = null;
        $subdir   = '';
        $errors   = [];

        $filenameEnable = config('app.filename_enable');
        $filenameForce  = config('app.filename_force');
        $subdirEnable   = config('app.subdir_enable');
        $subdirForce    = config('app.subdir_force');

        if ($filenameEnable && isset($_REQUEST['filename'])) {
            $filename = trim($_REQUEST['filename']);

            // to ensure uniqueness of "hash" when using provided filenames we
            // prepend 8-characters (calculated as CRC32 hash of the file) to
            // the name which should avoid collisions of same name files
            $filename = hash_file('crc32', $url) . '_' . $filename;
        }

        if ($filenameEnable && $filenameForce &&
            (isset($_FILES['postfile']) || isset($_FILES['postimage'])) && !$filename
        ) {
            // if filename is not provided but config says to force it - return error
            $errors[] = 'missing filename parameter';
        }

        if ($subdirEnable && isset($_REQUEST['subdir'])) {
            $subdir = Str::stripSlash($_REQUEST['subdir'], Str::BOTH_SLASH);
        }

        if ($subdirEnable && $subdirForce && (isset($_FILES['postfile']) || isset($_FILES['postimage'])) && !$subdir) {
            // if subdir is not provided but config says to force it - return error
            $errors[] = 'missing subdir parameter';
        }

        if (!empty($errors)) {
            return ['status' => 'ERR', 'reason' => $errors];
        }

        $dupl = $this->isDuplicate($url, $filename);
        if ($dupl) {
            $hash    = $dupl[0];
            $subdir  = $dupl[1];
            $hashdir = $subdir . '/' . $hash;
            $url      = File::uploadDir($hashdir . '/' . $hash);
        } else {
            if ($filename !== null) {
                $hash = $filename;
            } else {
                $hash = File::getNewHash($type);
            }
            $hashdir = $subdir . '/' . $hash;
            $this->saveSHAOfFile($url, $hash, $subdir, $filename);
        }

        if ($dupl) {
            return [
                'status' => 'OK',
                'type'   => $type,
                'hash'   => $hash,
                'url'    => domain_path(relative_path($hash)),
                'domain' => domain_path()
            ];
        }

        mkdir(File::uploadDir($hashdir), 0777, true);
        $file = File::uploadDir($hashdir . '/' . $hash);

        file_put_contents($file, file_get_contents($url));

        // remove all exif data from jpeg
        if ($type == 'jpg') {
            $res = \imagecreatefromjpeg($file);
            \imagejpeg(
                $res,
                $file,
                ($this->config !== null ? $this->config->get('app.jpeg_compression') : 90)
            );
        }

        if ($this->config->get('log_uploader', true)) {
            $fh = fopen(File::uploadDir('uploads.txt'), 'a');
            fwrite($fh, time() . ';' . $url . ';' . $hash . ';' . Utils::getUserIP() . "\n");
            fclose($fh);
        }

        return [
            'status'     => 'OK',
            'type'       => $type,
            'hash'       => $hash,
            'url'        => domain_path(relative_path($hash)),
            'domain'     => domain_path(),
            'deletecode' => $this->generateDeleteCodeForImage($hash)
        ];
    }

    /**
     * @param string      $file
     * @param string|null $filename
     *
     * @return array|bool [hash,subdir]
     */
    public function isDuplicate($file, $filename = null)
    {
        $sha_file = File::uploadDir('hashes.csv');
        if (!file_exists($sha_file)) {
            return false;
        }

        // calculate sha of file content (and filename if given)
        $sha = sha1_file($file);
        if ($filename !== null) {
            $sha = sha1($sha . $filename);
        }

        // and check for calculated sha within hashes.csv
        $fp = fopen($sha_file, 'r');
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if (!$line) {
                continue;
            }
            $sha_upload = substr($line, 0, 40);
            if ($sha_upload == $sha) { //when it's a duplicate return the hash of the original file
                fclose($fp);
                $sstr = substr($line, 41);
                return explode(';', $sstr);
            }
        }

        fclose($fp);

        return false;
    }

    /**
     * @param string      $filepath
     * @param string      $hash
     * @param string      $subdir
     * @param string|null $filename
     */
    public function saveSHAOfFile($filepath, $hash, $subdir = '', $filename = null)
    {
        // calculate sha of file content (and filename if given)
        $sha = sha1_file($filepath);
        if ($filename !== null) {
            $sha = sha1($sha . $filename);
        }

        // and save calculated sha (along with hash and subdir) into hashes.csv
        $sha_file = File::uploadDir('hashes.csv');
        $fp       = fopen($sha_file, 'a');
        fwrite($fp, "${sha};${hash};${subdir}\n");
        fclose($fp);
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    public function generateDeleteCodeForImage($hash)
    {
        while (1) {
            $code = Str::getRandomString(32);
            $file = root_path('upload/deletecodes/' . $code);
            if (file_exists($file)) {
                continue;
            }
            file_put_contents($file, $hash);
            return $code;
        }
    }

    /**
     * @return bool|string
     */
    public function processUploads()
    {
        if ($_POST['submit'] != Translator::translate(3)) {
            return false;
        }

        if ($this->config->get('app.upload_code', false) && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            return '<span class="error">' . Translator::translate(21) . '</span>';
        }

        //$im = $this->imageTransformer;
        $o  = '';
        $i  = 0;

        foreach ($_FILES["pic"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $data = $this->uploadFileFromURL($_FILES["pic"]["tmp_name"][$key]);

                if ($data['status'] == 'OK') {
                    if ($data['deletecode']) {
                        $deletecode = '<br/><a target="_blank" href="' . domain_path(relative_path($data['hash'] .
                                      '/delete_' . $data['deletecode'])) . '">Delete image</a>';
                    } else {
                        $deletecode = '';
                    }
                    if ($data['type'] == 'mp4') {
                        $o .= '<div><h2>' . Translator::translate(4) . ' ' . ++$i .
                              '</h2><a target="_blank" href="' . domain_path(relative_path($data['hash'])) . '">' .
                              $data['hash'] . '</a>' . $deletecode . '</div>';
                    } else {
                        $o .= '<div><h2>' . Translator::translate(4) . ' ' . ++$i .
                              '</h2><a target="_blank" href="' . domain_path(relative_path($data['hash'])) .
                              '"><img src="' . domain_path(relative_path('300/' . $data['hash'])) . '" />' .
                              '</a>' . $deletecode . '</div>';
                    }

                    $hashes[] = $data['hash'];
                }
            }
        }

        if (isset($hashes) && count($hashes) > 1) {
            $albumlink = domain_path(relative_path(implode('/', $hashes)));
            $o         .= '<hr/><h1>Album link</h1><a href="' . $albumlink . '" >' . $albumlink . '</a>';

            $iframe = '<iframe frameborder="0" width="100%" height="500" src="' . $albumlink .
                      '/300x300/forcesize/embed" <p>iframes are not supported by your browser.</p> </iframe>';
            $o      .= '<hr/><h1>Embed code</h1><input style="border:1px solid black;" size="100" type="text" value="' .
                       addslashes(htmlentities($iframe)) . '" />';
        }

        return $o;
    }

    /**
     * @param string $data
     * @param bool  $type
     *
     * @return array
     */
    public function uploadImageFromBase64($data, $type = false)
    {
        // if we are not given a type in request, we calculate it from data
        if (!$type) {
            $type = $this->base64ToType($data);
        }

        // if we still don't have the type then we can't do anything
        if (!$type) {
            return [
                'status' => 'ERR',
                'reason' => 'wrong filetype',
                'type'   => $type
            ];
        }

        $hash    = File::getNewHash($type);
        //$picname = $hash;
        $file    = root_path('tmp/' . $hash);
        $this->base64ToImage($data, $file, $type);

        return $this->uploadFileFromURL($file, $type);
    }

    /**
     * @param string $base64_string
     *
     * @return bool|string
     */
    public function base64ToType($base64_string)
    {
        $data = explode(',', $base64_string);
        $data = $data[1];

        $data = str_replace(' ', '+', $data);
        $data = base64_decode($data);

        $info = getimagesizefromstring($data);

        trigger_error("########## FILETYPE: " . $info['mime']);

        $f    = finfo_open();
        $type = $this->isTypeAllowed(finfo_buffer($f, $data, FILEINFO_MIME_TYPE));

        return $type;
    }

    /**
     * @param string $base64_string
     * @param string $output_file
     * @param string $type
     *
     * @return mixed
     */
    public function base64ToImage($base64_string, $output_file, $type)
    {
        $data = explode(',', $base64_string);
        $data = $data[1];

        $data = str_replace(' ', '+', $data);

        $data = base64_decode($data);

        $source = imagecreatefromstring($data);
        switch ($type) {
            case 'jpg':
                imagejpeg(
                    $source,
                    $output_file,
                    ($this->config !== null ? $this->config->get('app.jpeg_compression') : 90)
                );
                trigger_error("========= SAVING AS " . $type . " TO " . $output_file);
                break;

            case 'png':
                imagepng(
                    $source,
                    $output_file,
                    ($this->config !== null ? $this->config->get('app.png_compression') : 6)
                );
                trigger_error("========= SAVING AS " . $type . " TO " . $output_file);
                break;

            case 'gif':
                imagegif($source, $output_file);
                trigger_error("========= SAVING AS " . $type . " TO " . $output_file);
                break;

            default:
                imagepng(
                    $source,
                    $output_file,
                    ($this->config !== null ? $this->config->get('app.png_compression') : 6)
                );
                break;
        }

        //$imageSave = imagejpeg($source,$output_file,100);
        imagedestroy($source);

        return $type;
    }

    /**
     * @param string $gifpath
     * @param string $target
     *
     * @return mixed
     */
    public function gifToMP4($gifpath, $target)
    {
        $bin  = escapeshellcmd(root_path('bin/ffmpeg'));
        $file = escapeshellarg($gifpath);

        if (!file_exists($target)) { //simple caching.. have to think of something better
            $cmd = "$bin -f gif -y -i $file -c:v libx264 -f mp4 $target";
            system($cmd);
        }


        return $target;
    }

    /**
     * @param string $source
     * @param string $target
     */
    public function saveAsOGG($source, $target)
    {
        $bin    = escapeshellcmd(root_path('bin/ffmpeg'));
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $h265   = "$bin -y -i $source -vcodec libtheora -acodec libvorbis -qp 0 -f ogg $target";
        system($h265);
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool
     */
    public function saveAsWebm($source, $target)
    {
        return false;
        //$bin    = escapeshellcmd(root_path('/bin/ffmpeg'));
        //$source = escapeshellarg($source);
        //$target = escapeshellarg($target);
        //$webm   = "$bin -y -i $source -vcodec libvpx -acodec libvorbis -aq 5 -ac 2 -qmax 25 -f webm $target";
        //system($webm);
    }

    /**
     * @param string $path
     * @param string $target
     */
    public function saveFirstFrameOfMP4($path, $target)
    {
        $bin  = escapeshellcmd(root_path('bin/ffmpeg'));
        $file = escapeshellarg($path);
        $cmd  = "$bin -y -i $file -vframes 1 -f image2 $target";

        system($cmd);
    }

    /**
     * @param string $url
     * @param string $type
     *
     * @return array
     */
    public function oembed($url, $type)
    {
        $data   = $this->getURLInfo($url);
        $rawurl = $url . '/raw';

        switch ($type) {
            case 'json':
                header('Content-Type: application/json');
                return [
                    "version"          => "1.0",
                    "type"             => "video",
                    "thumbnail_url"    => $url . '/preview',
                    "thumbnail_width"  => $data['width'],
                    "thumbnail_height" => $data['height'],
                    "width"            => $data['width'],
                    "height"           => $data['height'],
                    "title"            => "PictShare",
                    "provider_name"    => "PictShare",
                    "provider_url"     => domain_path(),
                    "html"             => '<video id="video" poster="' . $url . '/preview' . '" 
                                                  preload="auto" autoplay="autoplay" muted="muted" 
                                                  loop="loop" webkit-playsinline>
                                            <source src="' . $rawurl . '" type="video/mp4">
                                          </video>'
                ];
                break;

            case 'xml':
                break;
        }

        return [];
    }

    /**
     * @param string $url
     * @param bool   $ispath
     *
     * @return array
     */
    public function getURLInfo($url, $ispath = false)
    {
        $url  = rawurldecode($url);
        $data = $this->urlToData($url);
        $hash = isset($data['hash']) ? $data['hash'] : false;
        if (! $hash) {
            return ['status' => 'ERR', 'Reason' => 'Image not found'];
        }

        $subdir  = $data['subdir'];
        $hashdir = $subdir . '/' . $hash;

        $file = $this->getCacheName($data);

        $path = File::uploadDir($hashdir . '/' . $file);
        if (!file_exists($path)) {
            $path = File::uploadDir($hashdir . '/' . $hash);
        }
        if (file_exists($path)) {
            $type = $this->getType($path);
            if ($ispath) {
                $byte = filesize($url);
            } else {
                $byte = filesize($path);
            }


            if ($type == 'mp4') {
                $info   = $this->getSizeOfMP4($path);
                $width  = intval($info['width']);
                $height = intval($info['height']);
            } else {
                list($width, $height) = getimagesize($path);
            }
            return [
                'status'    => 'ok',
                'hash'      => $hash,
                'cachename' => $file,
                'size'      => $byte,
                'humansize' => File::renderSize($byte),
                'width'     => $width,
                'height'    => $height,
                'type'      => $type
            ];
        } else {
            return ['status' => 'ERR', 'Reason' => 'Image not found'];
        }
    }

    /**
     * @param string $url
     *
     * @return array|bool
     */
    public function urlToData($url)
    {
        $urlArr = explode("/", $url);
        $data   = [];

        $masterDeleteCode = $this->config->get('app.master_delete_code');

        for ($i = 0, $j = count($urlArr); $i < $j; $i++) {
            $el   = Str::sanitize($urlArr[$i]);
            $orig = $el;
            $el   = strtolower($el);
            if (!$el) {
                continue;
            }

            if ($this->config->get('app.image_change_code', false) && substr($el, 0, 10) == 'changecode') {
                $data['changecode'] = substr($el, 11);
            }

            if (($isFile = File::isFile($orig)) || ($i === ($j - 1))) {
                if ($el === 'robots.txt') {
                    continue;
                }

                $subdir = File::getSubDirFromHash($orig);

                if (!$isFile && (( $fetchScript = $this->config->get('app.fetch_script') ) !== false)) {
                    $hashdir = ($subdir !== '' ? $subdir . '/' : '') . $orig . '/' . $orig;
                    //$output = shell_exec($fetchScript . ' ' . $hashdir);
                    $output = exec($fetchScript . ' ' . $hashdir, $outputArr, $returnVar);

                    //if (mb_stripos($output, 'OK') === false) {
                    if (trim($output) !== 'OK') {
                        $data['error_message'] = implode("\n", $outputArr);
                        continue;
                    }
                }

                // if there are more than one hashes in url make an album from them
                if ($data['hash']) {
                    if (! isset($data['album'])) {
                        $data['album'][] = $data['hash'];
                    }
                    $data['album'][] = $orig;
                }
                $data['hash']   = $orig;
                $data['subdir'] = $subdir;
            } elseif ($el == 'mp4' || $el == 'raw' || $el == 'preview' || $el == 'webm' || $el == 'ogg') {
                $data[$el] = 1;
            } elseif (File::isSize($el)) {
                $data['size'] = $el;
            } elseif ($el == 'embed') {
                $data['embed'] = true;
            } elseif ($el == 'responsive') {
                $data['responsive'] = true;
            } elseif (File::isRotation($el)) {
                $data['rotate'] = $el;
            } elseif (File::isFilter($el)) {
                $data['filter'][] = $el;
            } elseif ($legacy = File::isLegacyThumbnail($el)) { //so old uploads will still work
                $data['hash']   = $legacy['hash'];
                $data['subdir'] = File::getSubDirFromHash($data['hash']);
                $data['size']   = $legacy['size'];
            } elseif ($el == 'forcesize') {
                $data['forcesize'] = true;
            } elseif (strlen($masterDeleteCode) > 10 && $el == 'delete_' . $masterDeleteCode) {
                $data['delete'] = true;
            } elseif ($el == 'delete' && $this->mayDeleteImages() === true) {
                $data['delete'] = true;
            } elseif ((strlen($masterDeleteCode) > 10 && $el == 'delete_' . $masterDeleteCode) ||
                $this->deleteCodeExists($el)) {
                $data['delete'] = $this->deleteCodeExists($el) ? $el : true;
            }
        }

        if (isset($data['delete'], $data['hash']) && $data['delete'] && $data['hash']) {
            if ($data['delete'] === true || $this->isThisDeleteCodeForImage($data['delete'], $data['hash'], true)) {
                $this->deleteImage($data['hash']);
            }
            return false;
        }

        if (isset($data['mp4']) && $data['mp4']) {
            $hash   = isset($data['hash']) ? $data['hash'] : false;
            $subdir = isset($data['subdir']) ? $data['subdir'] : '';
            if (! $hash || $this->getTypeOfHash($hash, $subdir) != 'gif') {
                unset($data['mp4']);
            }
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function mayDeleteImages()
    {
        $masterDeleteIp = $this->config->get('app.master_delete_ip');
        if (!$masterDeleteIp) {
            return false;
        }

        $ip    = Utils::getUserIP();
        $parts = explode(';', $masterDeleteIp);

        foreach ($parts as $part) {
            if (strpos($part, '/') !== false) {   //it's a CIDR address
                if (Utils::cidrMatch($ip, $part)) {
                    return true;
                }
            } elseif (Utils::isIP($part)) {       //it's an IP address
                if ($part == $ip) {
                    return true;
                }
            } elseif (gethostbyname($part) == $ip) { //must be a hostname
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function deleteCodeExists($code)
    {
        if (strpos($code, '_')) {
            $code = substr($code, strpos($code, '_') + 1);
        }
        if (!$code || !ctype_alnum($code)) {
            return false;
        }
        $file = root_path('upload/deletecodes/' . $code);
        return file_exists($file);
    }

    /**
     * @param string $code
     * @param string $hash
     * @param bool   $deleteiftrue
     *
     * @return bool
     */
    public function isThisDeleteCodeForImage($code, $hash, $deleteiftrue = false)
    {
        if (strpos($code, '_')) {
            $code = substr($code, strpos($code, '_') + 1);
        }
        if (!ctype_alnum($code) || !$hash) {
            return false;
        }
        $file = root_path('upload/deletecodes/' . $code);
        if (!file_exists($file)) {
            return false;
        }
        $rhash = trim(file_get_contents($file));

        $result = ($rhash == $hash) ? true : false;

        if ($deleteiftrue === true && $result === true) {
            unlink($file);
        }

        return $result;
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function deleteImage($hash)
    {
        // delete hash from hashes.csv
        $tmpname = File::uploadDir('delete_temp.csv');
        $csv     = File::uploadDir('hashes.csv');
        $fptemp  = fopen($tmpname, "w");
        if (($handle = fopen($csv, "r")) !== false) {
            while (($line = fgets($handle)) !== false) {
                $data = explode(';', $line);
                if ($hash != trim($data[1])) {
                    fwrite($fptemp, $line);
                } else {
                    $subdir = $data[2];
                }
            }
        }
        fclose($handle);
        fclose($fptemp);
        unlink($csv);
        rename($tmpname, $csv);
        //unlink($tmpname);

        $subdir = isset($subdir) ? $subdir . '/' : '';

        // delete actual image
        $base_path = File::uploadDir($subdir . $hash . '/');
        if (!is_dir($base_path)) {
            return false;
        }
        if ($handle = opendir($base_path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    unlink($base_path . $entry);
                }
            }
            closedir($handle);
        }

        rmdir($base_path);

        return true;
    }

    /**
     * @param string $hash
     * @param string $subdir
     *
     * @return bool|string
     */
    public function getTypeOfHash($hash, $subdir)
    {
        $base_path = File::uploadDir($subdir . '/' . $hash . '/');
        $path      = $base_path . $hash;
        $type      = $this->isTypeAllowed($this->getTypeOfFile($path));

        return $type;
    }

    //from https://stackoverflow.com/questions/4847752/how-to-get-video-duration-dimension-and-size-in-php

    /**
     * @param array $data
     *
     * @return string
     */
    public function getCacheName($data)
    {
        ksort($data);
        unset($data['raw']);
        //unset($data['preview']);
        $name = false;
        foreach ($data as $key => $val) {
            if ($key != 'hash' && $key != 'subdir') {
                if (!is_array($val)) {
                    $name[] = $key . '_' . $val;
                } else {
                    foreach ($val as $valdata) {
                        $name[] = $valdata;
                    }
                }
            }
        }

        if (is_array($name)) {
            $name = implode('.', $name);
        }

        return ($name ? $name . '.' : '') . $data['hash'];
    }

    /**
     * @param string $url
     *
     * @return bool|string
     */
    public function getType($url)
    {
        return $this->isTypeAllowed($this->getTypeOfFile($url));
    }

    /**
     * @param string $video
     *
     * @return array
     */
    public function getSizeOfMP4($video)
    {
        $video   = escapeshellarg($video);
        $bin     = escapeshellcmd(root_path('bin/ffmpeg'));
        $command = $bin . ' -i ' . $video . ' -vstats 2>&1';
        $output  = shell_exec($command);

        $codec  = null;
        $width  = null;
        $height = null;
        $hours  = null;
        $mins   = null;
        $secs   = null;
        $ms     = null;

        $regex_sizes = "/Video: ([^,]*), ([^,]*), ([0-9]{1,4})x([0-9]{1,4})/";
        if (preg_match($regex_sizes, $output, $regs)) {
            $codec  = $regs [1] ? $regs [1] : $codec;
            $width  = $regs [3] ? $regs [3] : $width;
            $height = $regs [4] ? $regs [4] : $height;
        }

        $regex_duration = "/Duration: ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2}).([0-9]{1,2})/";
        if (preg_match($regex_duration, $output, $regs)) {
            $hours = $regs [1] ? $regs [1] : $hours;
            $mins  = $regs [2] ? $regs [2] : $mins;
            $secs  = $regs [3] ? $regs [3] : $secs;
            $ms    = $regs [4] ? $regs [4] : $ms;
        }

        return [
            'codec'  => $codec,
            'width'  => $width,
            'height' => $height,
            'hours'  => $hours,
            'mins'   => $mins,
            'secs'   => $secs,
            'ms'     => $ms
        ];
    }
}
