<?php

namespace App\Models;

use App\Support\ConfigInterface;
use App\Support\HTML;
use App\Support\String;
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
     * @var HTML
     */
    protected $html;

    /**
     * @var ImageTransformer
     */
    protected $imageTransformer;

    /**
     * Model constructor.
     *
     * @param ConfigInterface  $config
     * @param HTML             $html
     * @param ImageTransformer $imageTransformer
     */
    public function __construct(ConfigInterface $config, HTML $html, ImageTransformer $imageTransformer)
    {
        $this->config           = $config;
        $this->html             = $html;
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
                if (!$this->isImage($hash)) {
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
     * @param string $hash
     *
     * @return bool
     */
    public function isImage($hash)
    {
        if (!$hash) {
            return false;
        }
        return $this->hashExists($hash);
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function hashExists($hash)
    {
        return is_dir(ROOT . '/upload/' . $hash);
    }

    /**
     * @param string $source
     * @param string $target
     */
    public function saveAsMP4($source, $target)
    {
        $bin    = escapeshellcmd(ROOT . '/bin/ffmpeg');
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

        if (!$this->hashExists($hash)) {
            return false;
        }

        if (function_exists('renderResizedImage')) {
            return renderResizedImage($size, $hash);
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function renderUploadForm()
    {
        $maxfilesize = (int) (ini_get('upload_max_filesize'));

        $upload_code_form = '';
        if ($this->config->get('app.upload_code', false)) {
            $upload_code_form = '<strong>' . $this->translate(20) .
                                ': </strong><input class="input" type="password" name="upload_code" value="' .
                                $_REQUEST['upload_code'] . '"><div class="clear"></div>';
        }

        return '
        <div class="clear"></div>
        <strong>' . $this->translate(0) . ': ' . $maxfilesize . 'MB / File</strong><br>
        <strong>' . $this->translate(1) . '</strong>
        <br><br>
        <form id="form" enctype="multipart/form-data" method="post">
            <div id="formular">
                ' . $upload_code_form . '
                <strong>' . $this->translate(4) . ': </strong>
                <input class="input" type="file" name="pic[]" multiple>
                <div class="clear"></div>
                <div class="clear"></div><br>
            </div>
            <input class="btn" style="font-size:15px;font-weight:bold;background-color:#74BDDE;padding:3px;" 
                   type="submit" id="submit" name="submit" value="' . $this->translate(3) . '" 
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
     * @param int    $index
     * @param string $params
     *
     * @return mixed
     */
    public function translate($index, $params = "")
    {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        //$lang = 'en';
        switch ($lang) {
            case "de":
                $words[0]  = 'Maximale Dateigröße';
                $words[1]  = 'Es können auch mehrere Bilder auf einmal ausgewählt werden!';
                $words[2]  = 'einfach, gratis, genial';
                $words[3]  = 'Foto hinaufladen';
                $words[4]  = 'Bild';
                $words[5]  = 'Die Datei ' . $params[0] . ' kann nicht hinaufgeladen werden, da der Dateityp "' .
                             $params[1] . '" nicht unterstützt wird.';
                $words[6]  = 'Fehler beim Upload von ' . $params;
                $words[7]  = 'Bild "' . $params . '"" wurde erfolgreich hochgeladen';
                $words[8]  = 'Skaliert auf';
                $words[9]  = 'Kleinansicht';
                $words[10] = 'für Verlinkungen und Miniaturvorschau in Foren';
                $words[11] = 'Allgemeiner Fehler';
                $words[12] = 'Fehler 404 - nicht gefunden';
                $words[13] = 'Fehler 403 - nicht erlaubt';
                $words[14] = 'Kein refferer';
                $words[15] = 'Verlinkte Seiten';
                $words[16] = 'Hinweis: Zugriffe über pictshare.net werden nicht gerechnet';
                $words[17] = 'Dieses Bild wurde ' . $params[0] . ' mal von ' . $params[1] .
                             ' verschiedenen IPs gesehen und hat ' . $params[2] . ' Traffic verursacht';
                $words[18] = 'Dieses Bild wurde von folgenden Ländern aufgerufen: ';
                $words[19] = $params[0] . ' Aufrufe aus ' . $params[1];
                $words[20] = 'Upload-Code';
                $words[21] = 'Falscher Upload Code eingegeben. Upload abgebrochen';

                break;

            default:
                $words[0]  = 'Max filesize';
                $words[1]  = 'You can select multiple pictures at once!';
                $words[2]  = 'easy, free, engenious';
                $words[3]  = 'Upload';
                $words[4]  = 'Picture';
                $words[5]  = 'The file ' . $params[0] . ' can\'t be uploaded since the filetype "' . $params[1] .
                             '" is not supported.';
                $words[6]  = 'Error uploading ' . $params;
                $words[7]  = 'Picture "' . $params . '"" was uploaded successfully';
                $words[8]  = 'Scaled to';
                $words[9]  = 'Thumbnail';
                $words[10] = 'for pasting in Forums, etc..';
                $words[11] = 'Unspecified error';
                $words[12] = 'Error 404 - not found';
                $words[13] = 'Error 403 - not allowed';
                $words[14] = 'No referrer';
                $words[15] = 'Linked sites';
                $words[16] = 'Note: Views from pictshare.net will not be counted';
                $words[17] = 'Was seen ' . $params[0] . ' times by ' . $params[1] . ' unique IPs and produced ' .
                             $params[2] . ' traffic';
                $words[18] = 'This picture was seen from the following countries: ';
                $words[19] = $params[0] . ' views from ' . $params[1];
                $words[20] = 'Upload code';
                $words[21] = 'Invalid upload code provided';
        }

        return $words[$index];
    }

    /**
     * @param string $hash
     *
     * @return int
     */
    public function countResizedImages($hash)
    {
        $fi = new \FilesystemIterator(ROOT . '/upload/' . $hash . '/', \FilesystemIterator::SKIP_DOTS);
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
     * @param $file
     * @param $name
     *
     * @return array
     */
    public function processSingleUpload($file, $name)
    {
        if ($this->config->get('app.upload_code', false) && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            exit(json_encode(['status' => 'ERR', 'reason' => $this->translate(21)]));
        }

        //$im = $this->imageTransformer;
        $o  = [];

        if ($_FILES[$name]["error"] == UPLOAD_ERR_OK) {
            $type = $this->getTypeOfFile($_FILES[$name]["tmp_name"]);
            $type = $this->isTypeAllowed($type);
            if (!$type) {
                exit(json_encode(['status' => 'ERR', 'reason' => 'Unsupported type']));
            }

            $data = $this->uploadImageFromURL($_FILES[$name]["tmp_name"]);
            if ($data['status'] == 'OK') {
                $hash = $data['hash'];
                $o    = [
                    'status' => 'OK',
                    'type'   => $type,
                    'hash'   => $hash,
                    'url'    => DOMAINPATH . '/' . $hash,
                    'domain' => DOMAINPATH
                ];
                if ($data['deletecode']) {
                    $o['deletecode'] = $data['deletecode'];
                }

                return $o;
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
     * @param string $url
     *
     * @return bool|string
     */
    public function getTypeOfFile($url)
    {
        $fi   = new \finfo(FILEINFO_MIME);
        $type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));

        //to catch a strange error for PHP7 and Alpine Linux
        //if the file seems to be a stream, use unix file command
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' &&
            String::startsWith($type, 'application/octet-stream')
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
        $tmp  = ROOT . '/tmp/' . md5(time() + rand(1, 10000)) . '.' . rand(1, 10000) . '.log';
        $bin  = escapeshellcmd(ROOT . '/bin/ffmpeg');

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
                return false;
        }
    }

    /**
     * @param string $url
     *
     * @return array
     */
    public function uploadImageFromURL($url)
    {
        $type = $this->getTypeOfFile($url);
        $type = $this->isTypeAllowed($type);

        if (!$type) {
            return ['status' => 'ERR', 'reason' => 'wrong filetype'];
        }

        $dup_id = $this->isDuplicate($url);
        if ($dup_id) {
            $hash = $dup_id;
            $url  = ROOT . '/upload/' . $hash . '/' . $hash;
        } else {
            $hash = $this->getNewHash($type);
            $this->saveSHAOfFile($url, $hash);
        }


        if ($dup_id) {
            return [
                'status' => 'OK',
                'type'   => $type,
                'hash'   => $hash,
                'url'    => DOMAINPATH . PATH . $hash,
                'domain' => DOMAINPATH
            ];
        }

        mkdir(ROOT . '/upload/' . $hash);
        $file = ROOT . '/upload/' . $hash . '/' . $hash;

        file_put_contents($file, file_get_contents($url));

        //remove all exif data from jpeg
        if ($type == 'jpg') {
            $res = \imagecreatefromjpeg($file);
            \imagejpeg(
                $res,
                $file,
                ($this->config !== null ? $this->config->get('app.jpeg_compression') : 90)
            );
        }

        if ($this->config->get('log_uploader', true)) {
            $fh = fopen(ROOT . '/upload/uploads.txt', 'a');
            fwrite($fh, time() . ';' . $url . ';' . $hash . ';' . Utils::getUserIP() . "\n");
            fclose($fh);
        }

        return [
            'status'     => 'OK',
            'type'       => $type,
            'hash'       => $hash,
            'url'        => DOMAINPATH . PATH . $hash,
            'domain'     => DOMAINPATH,
            'deletecode' => $this->generateDeleteCodeForImage($hash)
        ];
    }

    /**
     * @param string $file
     *
     * @return bool|string
     */
    public function isDuplicate($file)
    {
        $sha_file = ROOT . '/upload/hashes.csv';
        $sha      = sha1_file($file);
        if (!file_exists($sha_file)) {
            return false;
        }
        $fp = fopen($sha_file, 'r');
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if (!$line) {
                continue;
            }
            $sha_upload = substr($line, 0, 40);
            if ($sha_upload == $sha) { //when it's a duplicate return the hash of the original file
                fclose($fp);
                return substr($line, 41);
            }
        }

        fclose($fp);

        return false;
    }

    /**
     * @param string $type
     * @param int    $length
     *
     * @return string
     */
    public function getNewHash($type, $length = 10)
    {
        while (1) {
            $hash = String::getRandomString($length) . '.' . $type;
            if (!$this->hashExists($hash)) {
                return $hash;
            }
        }
    }

    /**
     * @param string $filepath
     * @param string $hash
     */
    public function saveSHAOfFile($filepath, $hash)
    {
        $sha_file = ROOT . '/upload/hashes.csv';
        $sha      = sha1_file($filepath);
        $fp       = fopen($sha_file, 'a');
        fwrite($fp, "$sha;$hash\n");
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
            $code = String::getRandomString(32);
            $file = ROOT . '/upload/deletecodes/' . $code;
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
        if ($_POST['submit'] != $this->translate(3)) {
            return false;
        }

        if ($this->config->get('app.upload_code', false) && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            return '<span class="error">' . $this->translate(21) . '</span>';
        }

        //$im = $this->imageTransformer;
        $o  = '';
        $i  = 0;

        foreach ($_FILES["pic"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $data = $this->uploadImageFromURL($_FILES["pic"]["tmp_name"][$key]);

                if ($data['status'] == 'OK') {
                    if ($data['deletecode']) {
                        $deletecode = '<br/><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] .
                                      '/delete_' . $data['deletecode'] . '">Delete image</a>';
                    } else {
                        $deletecode = '';
                    }
                    if ($data['type'] == 'mp4') {
                        $o .= '<div><h2>' . $this->translate(4) . ' ' . ++$i . '</h2><a target="_blank" href="' .
                              DOMAINPATH . PATH . $data['hash'] . '">' . $data['hash'] . '</a>' . $deletecode .
                              '</div>';
                    } else {
                        $o .= '<div><h2>' . $this->translate(4) . ' ' . ++$i . '</h2><a target="_blank" href="' .
                              DOMAINPATH . PATH . $data['hash'] . '"><img src="' . DOMAINPATH . PATH . '300/' .
                              $data['hash'] . '" /></a>' . $deletecode . '</div>';
                    }

                    $hashes[] = $data['hash'];
                }
            }
        }

        if (isset($hashes) && count($hashes) > 1) {
            $albumlink = DOMAINPATH . PATH . implode('/', $hashes);
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
        $type = $this->base64ToType($data);
        if (!$type) {
            return [
                'status' => 'ERR',
                'reason' => 'wrong filetype',
                'type'   => $type
            ];
        }
        $hash    = $this->getNewHash($type);
        $picname = $hash;
        $file    = ROOT . '/tmp/' . $hash;
        $this->base64ToImage($data, $file, $type);

        return $this->uploadImageFromURL($file);
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
     * @param array  $data
     * @param string $cachepath
     * @param string $type
     *
     * @return string
     */
    public function resizeFFMPEG($data, $cachepath, $type = 'mp4')
    {
        $file = ROOT . '/upload/' . $data['hash'] . '/' . $data['hash'];
        $file = escapeshellarg($file);
        $tmp  = '/dev/null';
        $bin  = escapeshellcmd(ROOT . '/bin/ffmpeg');

        $size = $data['size'];

        if (!$size) {
            return $file;
        }

        $sd        = $this->sizeStringToWidthHeight($size);
        $maxwidth  = $sd['width'];
        //$maxheight = $sd['height'];
        $maxheight = 'trunc(ow/a/2)*2';
        $addition  = '';

        switch ($type) {
            case 'mp4':
                $addition = '-c:v libx264';
                break;
        }

        $cmd = "$bin -i $file -y -vf scale=\"$maxwidth:$maxheight\" $addition -f $type $cachepath";

        system($cmd);

        return $cachepath;
    }

    /**
     * @param int|string $size
     *
     * @return array|bool
     */
    public function sizeStringToWidthHeight($size)
    {
        if (!$size || !$this->isSize($size)) {
            return false;
        }
        if (!is_numeric($size)) {
            $size = explode('x', $size);
        }

        $maxheight = 0;
        $maxwidth  = 0;

        if (is_array($size)) {
            $maxwidth  = $size[0];
            $maxheight = $size[1];
        } elseif ($size) {
            $maxwidth  = $size;
            $maxheight = $size;
        }

        return ['width' => $maxwidth, 'height' => $maxheight];
    }

    /**
     * @param mixed $var
     *
     * @return bool
     */
    public function isSize($var)
    {
        if (is_numeric($var)) {
            return true;
        }
        $a = explode('x', $var);
        if (count($a) != 2 || !is_numeric($a[0]) || !is_numeric($a[1])) {
            return false;
        }

        return true;
    }

    /**
     * @param string $gifpath
     * @param string $target
     *
     * @return mixed
     */
    public function gifToMP4($gifpath, $target)
    {
        $bin  = escapeshellcmd(ROOT . '/bin/ffmpeg');
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
        $bin    = escapeshellcmd(ROOT . '/bin/ffmpeg');
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
        //$bin    = escapeshellcmd(ROOT . '/bin/ffmpeg');
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
        $bin  = escapeshellcmd(ROOT . '/bin/ffmpeg');
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
                    "provider_url"     => DOMAINPATH,
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
        $hash = $data['hash'];
        if (!$hash) {
            return ['status' => 'ERR', 'Reason' => 'Image not found'];
        }

        $file = $this->getCacheName($data);

        $path = ROOT . '/upload/' . $hash . '/' . $file;
        if (!file_exists($path)) {
            $path = ROOT . '/upload/' . $hash . '/' . $hash;
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
                'humansize' => $this->html->renderSize($byte),
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
        $url  = explode("/", $url);
        $data = [];

        foreach ($url as $el) {
            $el = $this->html->sanatizeString($el);
            $el = strtolower($el);
            if (!$el) {
                continue;
            }

            if ($this->config->get('app.image_change_code', false) && substr($el, 0, 10) == 'changecode') {
                $data['changecode'] = substr($el, 11);
            }

            $masterDeleteCode = $this->config->get('app.master_delete_code');

            if ($this->isImage($el)) {
                //if there are mor than one hashes in url
                //make an album from them
                if ($data['hash']) {
                    if (!$data['album']) {
                        $data['album'][] = $data['hash'];
                    }
                    $data['album'][] = $el;
                }
                $data['hash'] = $el;

            } elseif ($el == 'mp4' || $el == 'raw' || $el == 'preview' || $el == 'webm' || $el == 'ogg') {
                $data[$el] = 1;

            } elseif ($this->isSize($el)) {
                $data['size'] = $el;

            } elseif ($el == 'embed') {
                $data['embed'] = true;

            } elseif ($el == 'responsive') {
                $data['responsive'] = true;

            } elseif ($this->isRotation($el)) {
                $data['rotate'] = $el;

            } elseif ($this->isFilter($el)) {
                $data['filter'][] = $el;

            } elseif ($legacy = $this->isLegacyThumbnail($el)) { //so old uploads will still work
                $data['hash'] = $legacy['hash'];
                $data['size'] = $legacy['size'];

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
            $hash = $data['hash'];
            if (!$hash || $this->getTypeOfHash($hash) != 'gif') {
                unset($data['mp4']);
            }
        }

        return $data;
    }

    /**
     * @param string $var
     *
     * @return bool
     */
    public function isRotation($var)
    {
        switch ($var) {
            case 'upside':
            case 'left':
            case 'right':
                return true;

            default:
                return false;
        }
    }

    /**
     * @param string $var
     *
     * @return bool
     */
    public function isFilter($var)
    {
        if (strpos($var, '_')) {
            $a   = explode('_', $var);
            $var = $a[0];
            $val = $a[1];
            if (!is_numeric($val)) {
                return false;
            }
        }

        switch ($var) {
            case 'negative':
            case 'grayscale':
            case 'brightness':
            case 'edgedetect':
            case 'smooth':
            case 'contrast':
            case 'blur':
            case 'sepia':
            case 'sharpen':
            case 'emboss':
            case 'cool':
            case 'light':
            case 'aqua':
            case 'fuzzy':
            case 'boost':
            case 'gray':
            case 'pixelate':
                return true;

            default:
                return false;
        }
    }

    /**
     * @param string $val
     *
     * @return array|bool
     */
    public function isLegacyThumbnail($val)
    {
        if (strpos($val, '_')) {
            $a    = explode('_', $val);
            $size = $a[0];
            $hash = $a[1];
            if (!$this->isSize($size) || !$this->isImage($hash)) {
                return false;
            }

            return ['hash' => $hash, 'size' => $size];
        } else {
            return false;
        }
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
        $file = ROOT . '/upload/deletecodes/' . $code;
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
        $file = ROOT . '/upload/deletecodes/' . $code;
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
        //delete hash from hashes.csv
        $tmpname = ROOT . '/upload/delete_temp.csv';
        $csv     = ROOT . '/upload/hashes.csv';
        $fptemp  = fopen($tmpname, "w");
        if (($handle = fopen($csv, "r")) !== false) {
            while (($line = fgets($handle)) !== false) {
                $data = explode(';', $line);
                if ($hash != trim($data[1])) {
                    fwrite($fptemp, $line);
                }
            }
        }
        fclose($handle);
        fclose($fptemp);
        unlink($csv);
        rename($tmpname, $csv);
        //unlink($tmpname);

        //delete actual image
        $base_path = ROOT . '/upload/' . $hash . '/';
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
     *
     * @return bool|string
     */
    public function getTypeOfHash($hash)
    {
        $base_path = ROOT . '/upload/' . $hash . '/';
        $path      = $base_path . $hash;
        $type      = $this->isTypeAllowed($this->getTypeOfFile($path));

        return $type;
    }

    // TODO: ???
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
            if ($key != 'hash') {
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
        $bin     = escapeshellcmd(ROOT . '/bin/ffmpeg');
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
