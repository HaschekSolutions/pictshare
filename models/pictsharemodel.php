<?php

use PictShare\Classes\FileSizeFormatter;
use PictShare\Classes\FilterFactory;
use PictShare\Classes\StorageProviderFactory;

class PictshareModel
{
    public function backend($params): array
    {
        switch ($params[0]) {
            case 'mp4convert':
                list($hash, $path) = $params;

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
     * @param string $url
     * @param bool $isPath
     *
     * @return array
     */
    public function getURLInfo(string $url, bool $isPath = false): array
    {
        $url  = rawurldecode($url);
        $data = $this->urlToData($url);
        $hash = $data['hash'];

        if (!$hash) {
            return [
                'status' => 'ERR',
                'Reason' => 'Image not found',
            ];
        }

        $file = $this->getCacheName($data);

        $path = ROOT . DS . 'upload' . DS . $hash . DS . $file;
        if (!file_exists($path)) {
            $path = ROOT . DS . 'upload' . DS . $hash . DS . $hash;
        }
        if (file_exists($path)) {
            $type = $this->getType($path);

            $byte = $isPath ? filesize($url) : filesize($path);

            if ($type === 'mp4') {
                $info = $this->getSizeOfMP4($path);
                $width = (int) $info['width'];
                $height = (int) $info['height'];
            } else {
                list($width, $height) = getimagesize($path);
            }

            return [
                'status' => 'ok',
                'hash' => $hash,
                'cachename' => $file,
                'size' => $byte,
                'humansize' => FileSizeFormatter::format($byte),
                'width' => $width,
                'height' => $height,
                'type' => $type,
            ];
        }

        return [
            'status' => 'ERR',
            'Reason' => 'Image not found',
        ];
    }

    public function urlToData($url): array
    {
        $url  = explode('/', $url);
        $data = [];

        foreach ($url as $el) {
            $el = preg_replace("/[^a-zA-Z0-9._\-]+/", '', $el);
            $el = strtolower($el);
            if (!$el) {
                continue;
            }

            if (IMAGE_CHANGE_CODE && strpos($el, 'changecode') === 0) {
                $data['changecode'] = substr($el, 11);
            }

            if ($this->isImage($el)) {
                //if there are more than one hashes in url
                //make an album from them
                if ($data['hash']) {
                    if (!$data['album']) {
                        $data['album'][] = $data['hash'];
                    }
                    $data['album'][] = $el;
                }
                $data['hash'] = $el;
            } elseif (defined('BACKBLAZE') && BACKBLAZE === true && defined('BACKBLAZE_AUTODOWNLOAD') && BACKBLAZE_AUTODOWNLOAD === true && $this->couldThisBeAnImage($el)) { //looks like it might be a hash but didn't find it here. Let's see
                $fileContent = StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER)
                    ->get($el, $el);

                if ($fileContent) { // If the backblaze get function says it's an image, we'll take it.
                    StorageProviderFactory::getStorageProvider(StorageProviderFactory::LOCAL_PROVIDER)
                        ->save($el, $el, $fileContent);

                    $data['hash'] = $el;
                }
            } elseif ($el === 'mp4' || $el === 'raw' || $el === 'preview' || $el === 'webm' || $el === 'ogg') {
                $data[$el] = 1;
            } elseif ($this->isSize($el)) {
                $data['size'] = $el;
            } elseif ($el === 'embed') {
                $data['embed'] = true;
            } elseif ($el === 'responsive') {
                $data['responsive'] = true;
            } elseif ($this->isRotation($el)) {
                $data['rotate'] = $el;
            } elseif ($this->isFilter($el)) {
                $data['filter'][] = $el;
            } elseif ($el === 'forcesize') {
                $data['forcesize'] = true;
            } elseif (strlen(MASTER_DELETE_CODE) > 10 && $el === 'delete_' . strtolower(MASTER_DELETE_CODE)) {
                $data['delete'] = true;
            } elseif ($el === 'delete' && $this->mayDeleteImages() === true) {
                $data['delete'] = true;
            } elseif ($this->deleteCodeExists($el) || (strlen(MASTER_DELETE_CODE) > 10 && $el === 'delete_' . strtolower(MASTER_DELETE_CODE))) {
                $data['delete'] = $this->deleteCodeExists($el) ? $el : true;
            }
        }

        if ($data['delete'] && $data['hash']) {
            if ($data['delete'] === true || $this->isThisDeleteCodeForImage($data['delete'], $data['hash'], true)) {
                $this->deleteImage($data['hash']);
            }
            return [];
        }

        if ($data['mp4']) {
            $hash = $data['hash'];
            if (!$hash || $this->getTypeOfHash($hash) !== 'gif') {
                unset($data['mp4']);
            }
        }

        return $data;
    }

    public function getCacheName(array $data): string
    {
        ksort($data);
        unset($data['raw']);

        $name = false;

        foreach ($data as $key => $val) {
            if ($key !== 'hash') {
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

    public function isImage($hash): bool
    {
        if (!$hash) {
            return false;
        }

        return $this->hashExists($hash);
    }

    public function renderUploadForm(): string
    {
        $maxFileSize    = (int) ini_get('upload_max_filesize');
        $uploadCodeForm = '';

        if (UPLOAD_CODE) {
            $uploadCodeForm = '<strong>' . $this->translate(20) . ': </strong><input class="input" type="password" name="upload_code" value="' . $_REQUEST['upload_code'] . '"><div class="clear"></div>';
        }

        return '
		<div class="clear"></div>
		<strong>' . $this->translate(0) . ': ' . $maxFileSize . 'MB / File</strong><br>
		<strong>' . $this->translate(1) . '</strong>
		<br><br>
		<FORM id="form" enctype="multipart/form-data" method="post">
		<div id="formular">
			' . $uploadCodeForm . '
			<strong>' . $this->translate(4) . ': </strong><input class="input" type="file" name="pic[]" multiple><div class="clear"></div>
			<div class="clear"></div><br>
		</div>
			<INPUT class="btn" style="font-size:15px;font-weight:bold;background-color:#74BDDE;padding:3px;" type="submit" id="submit" name="submit" value="' . $this->translate(3) . '" onClick="setTimeout(function(){document.getElementById(\'submit\').disabled = \'disabled\';}, 1);$(\'#movingBallG\').fadeIn()">
			<div id="movingBallG" class="invisible">
				<div class="movingBallLineG"></div>
				<div id="movingBallG_1" class="movingBallG"></div>
			</div>
		</FORM>';
    }

    public function countResizedImages(string $hash): int
    {
        $fi = new FilesystemIterator(ROOT . DS . 'upload' . DS . $hash . DS, FilesystemIterator::SKIP_DOTS);

        return iterator_count($fi);
    }

    public function getTypeOfFile($url)
    {
        $fi = new finfo(FILEINFO_MIME);
        $type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));

        //to catch a strange error for PHP7 and Alpine Linux
        //if the file seems to be a stream, use unix file command
        if (startsWith($type, 'application/octet-stream') && stripos(strtoupper(PHP_OS), 'WIN') !== 0) {
            $content_type = exec('file -bi ' . escapeshellarg($url));
            if ($content_type && $content_type !== $type && strpos($content_type, '/') !== false && strpos($content_type, ';') !== false) {
                $type = $content_type;
            }
        }

        $arr = explode(';', trim($type));
        if (count($arr) > 1) {
            $a2 = explode('/', $arr[0]);
            $type = $a2[1];
        } else {
            $a2 = explode('/', $type);
            $type = $a2[1];
        }

        if ($type === 'octet-stream' && $this->isProperMP4($url)) {
            return 'mp4';
        }
        if ($type === 'mp4' && !$this->isProperMP4($url)) {
            return false;
        }

        return $type;
    }

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

    public function uploadImageFromURL($url): array
    {
        $type = $this->getTypeOfFile($url);
        $type = $this->isTypeAllowed($type);

        if (!$type) {
            return [
                'status' => 'ERR',
                'reason' => 'wrong filetype',
            ];
        }

        $tempfile = ROOT . DS . 'tmp' . DS . md5(random_int(1, 999) * random_int(0, 10000) + time());
        file_put_contents($tempfile, file_get_contents($url));

        //remove all exif data from jpeg
        if ($type === 'jpg') {
            $res = imagecreatefromjpeg($tempfile);
            imagejpeg($res, $tempfile, (defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
        }
        $url = $tempfile;

        $dup_id = $this->isDuplicate($url);
        if ($dup_id) {
            $hash = $dup_id;
            $url = ROOT . DS . 'upload' . DS . $hash . DS . $hash;
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
                'domain' => DOMAINPATH,
            ];
        }

        $fileContent = file_get_contents($url);

        StorageProviderFactory::getStorageProvider(StorageProviderFactory::LOCAL_PROVIDER)
            ->save($hash, $hash, $fileContent);

        unlink($tempfile);

        //re-render new mp4 by calling the re-encode script
        if ($type === 'mp4' && stripos(strtoupper(PHP_OS), 'WIN')) {
            system('nohup php ' . ROOT . DS . 'tools' . DS . 're-encode_mp4.php force ' . $hash . ' > /dev/null 2> /dev/null &');
        }

        if (LOG_UPLOADER) {
            $fh = fopen(ROOT . DS . 'upload' . DS . 'uploads.txt', 'ab');
            fwrite($fh, time() . ';' . $url . ';' . $hash . ';' . getUserIP() . "\n");
            fclose($fh);
        }

        if (defined('BACKBLAZE')
            && BACKBLAZE === true
            && defined('BACKBLAZE_AUTOUPLOAD')
            && BACKBLAZE_AUTOUPLOAD === true
        ) {
            StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER)
                ->save($hash, $hash, $fileContent);
        }

        return [
            'status'     => 'OK',
            'type'       => $type,
            'hash'       => $hash,
            'url'        => DOMAINPATH . PATH . $hash,
            'domain'     => DOMAINPATH,
            'deletecode' => $this->generateDeleteCodeForImage($hash),
        ];
    }

    public function uploadCodeExists($code): bool
    {
        if (strpos(UPLOAD_CODE, ';')) {
            $codes = explode(';', UPLOAD_CODE);

            foreach ($codes as $ucode) {
                if ($code === $ucode) {
                    return true;
                }
            }
        }

        return $code === UPLOAD_CODE;
    }

    public function changeCodeExists($code): bool
    {
        if (!IMAGE_CHANGE_CODE) {
            return true;
        }

        if (strpos(IMAGE_CHANGE_CODE, ';')) {
            $codes = explode(';', IMAGE_CHANGE_CODE);

            foreach ($codes as $ucode) {
                if ($code === $ucode) {
                    return true;
                }
            }
        }

        return $code === IMAGE_CHANGE_CODE;
    }

    public function processSingleUpload($name): array
    {
        if (UPLOAD_CODE && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            exit(json_encode(['status' => 'ERR','reason' => $this->translate(21)]));
        }

        if ($_FILES[$name]['error'] == UPLOAD_ERR_OK) {
            $type = $this->getTypeOfFile($_FILES[$name]['tmp_name']);
            $type = $this->isTypeAllowed($type);
            if (!$type) {
                exit(json_encode(['status' => 'ERR','reason' => 'Unsupported type']));
            }

            $data = $this->uploadImageFromURL($_FILES[$name]['tmp_name']);
            if ($data['status'] === 'OK') {
                $o = [
                    'status' => 'OK',
                    'type' => $type,
                    'hash' => $data['hash'],
                    'url' => DOMAINPATH . '/' . $data['hash'],
                    'domain' => DOMAINPATH,
                ];

                if ($data['deletecode']) {
                    $o['deletecode'] = $data['deletecode'];
                }

                return $o;
            }
        }

        return [
            'status' => 'ERR',
            'reason' => 'Unknown',
        ];
    }

    public function processUploads()
    {
        if ($_POST['submit'] !== $this->translate(3)) {
            return false;
        }

        if (UPLOAD_CODE && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            return '<span class="error">' . $this->translate(21) . '</span>';
        }

        $i      = 0;
        $o      = '';
        $hashes = [];

        foreach ($_FILES['pic']['error'] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $data = $this->uploadImageFromURL($_FILES['pic']['tmp_name'][$key]);

                if ($data['status'] === 'OK') {
                    if ($data['deletecode']) {
                        $deletecode = '<br/><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] . '/delete_' . $data['deletecode'] . '">Delete image</a>';
                    } else {
                        $deletecode = '';
                    }
                    if ($data['type'] === 'mp4') {
                        $o .= '<div><h2>' . $this->translate(4) . ' ' . ++$i . '</h2><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] . '">' . $data['hash'] . '</a>' . $deletecode . '</div>';
                    } else {
                        $o .= '<div><h2>' . $this->translate(4) . ' ' . ++$i . '</h2><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] . '"><img src="' . DOMAINPATH . PATH . '300/' . $data['hash'] . '" /></a>' . $deletecode . '</div>';
                    }

                    $hashes[] = $data['hash'];
                }
            }
        }

        if (count($hashes) > 1) {
            $albumlink = DOMAINPATH . PATH . implode('/', $hashes);
            $o .= '<hr/><h1>Album link</h1><a href="' . $albumlink . '" >' . $albumlink . '</a>';

            $iframe = '<iframe frameborder="0" width="100%" height="500" src="' . $albumlink . '/300x300/forcesize/embed" <p>iframes are not supported by your browser.</p> </iframe>';
            $o .= '<hr/><h1>Embed code</h1><input style="border:1px solid black;" size="100" type="text" value="' . addslashes(htmlentities($iframe)) . '" />';
        }

        return $o;
    }

    public function translate($index, $params = '')
    {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        switch ($lang) {
            case 'de':
                $words[0] = 'Maximale Dateigröße';
                $words[1] = 'Es können auch mehrere Bilder auf einmal ausgewählt werden!';
                $words[2] = 'einfach, gratis, genial';
                $words[3] = 'Foto hinaufladen';
                $words[4] = 'Bild';
                $words[5] = 'Die Datei ' . $params[0] . ' kann nicht hinaufgeladen werden, da der Dateityp "' . $params[1] . '" nicht unterstützt wird.';
                $words[6] = 'Fehler beim Upload von ' . $params;
                $words[7] = 'Bild "' . $params . '"" wurde erfolgreich hochgeladen';
                $words[8] = 'Skaliert auf';
                $words[9] = 'Kleinansicht';
                $words[10] = 'für Verlinkungen und Miniaturvorschau in Foren';
                $words[11] = 'Allgemeiner Fehler';
                $words[12] = 'Fehler 404 - nicht gefunden';
                $words[13] = 'Fehler 403 - nicht erlaubt';
                $words[14] = 'Kein refferer';
                $words[15] = 'Verlinkte Seiten';
                $words[16] = 'Hinweis: Zugriffe über pictshare.net werden nicht gerechnet';
                $words[17] = 'Dieses Bild wurde ' . $params[0] . ' mal von ' . $params[1] . ' verschiedenen IPs gesehen und hat ' . $params[2] . ' Traffic verursacht';
                $words[18] = 'Dieses Bild wurde von folgenden Ländern aufgerufen: ';
                $words[19] = $params[0] . ' Aufrufe aus ' . $params[1];
                $words[20] = 'Upload-Code';
                $words[21] = 'Falscher Upload Code eingegeben. Upload abgebrochen';

                break;

            default:
                $words[0] = 'Max filesize';
                $words[1] = 'You can select multiple pictures at once!';
                $words[2] = 'easy, free, engenious';
                $words[3] = 'Upload';
                $words[4] = 'Picture';
                $words[5] = 'The file ' . $params[0] . ' can\'t be uploaded since the filetype "' . $params[1] . '" is not supported.';
                $words[6] = 'Error uploading ' . $params;
                $words[7] = 'Picture "' . $params . '"" was uploaded successfully';
                $words[8] = 'Scaled to';
                $words[9] = 'Thumbnail';
                $words[10] = 'for pasting in Forums, etc..';
                $words[11] = 'Unspecified error';
                $words[12] = 'Error 404 - not found';
                $words[13] = 'Error 403 - not allowed';
                $words[14] = 'No referrer';
                $words[15] = 'Linked sites';
                $words[16] = 'Note: Views from pictshare.net will not be counted';
                $words[17] = 'Was seen ' . $params[0] . ' times by ' . $params[1] . ' unique IPs and produced ' . $params[2] . ' traffic';
                $words[18] = 'This picture was seen from the following countries: ';
                $words[19] = $params[0] . ' views from ' . $params[1];
                $words[20] = 'Upload code';
                $words[21] = 'Invalid upload code provided';
        }

        return $words[$index];
    }

    public function uploadImageFromBase64($data): array
    {
        $type = $this->base64ToType($data);

        if (!$type) {
            return [
                'status' => 'ERR',
                'reason' => 'wrong filetype',
                'type'   => $type,
            ];
        }

        $hash = $this->getNewHash($type);
        $file = ROOT . DS . 'tmp' . DS . $hash;

        $this->base64ToImage($data, $file, $type);

        return $this->uploadImageFromURL($file);
    }

    public function resizeFFMPEG($data, $cachepath, $type = 'mp4'): string
    {
        $file = ROOT . DS . 'upload' . DS . $data['hash'] . DS . $data['hash'];
        $file = escapeshellarg($file);
        $bin  = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $size = $data['size'];

        if (!$size) {
            return $file;
        }

        $sd        = $this->sizeStringToWidthHeight($size);
        $maxwidth  = $sd['width'];
        $addition  = '';

        switch ($type) {
            case 'mp4':
                $addition = '-c:v libx264 -profile:v baseline -level 3.0 -pix_fmt yuv420p';
                break;
        }

        $maxheight = 'trunc(ow/a/2)*2';

        $cmd = "$bin -i $file -y -vf scale=\"$maxwidth:$maxheight\" $addition -f $type $cachepath";

        system($cmd);

        return $cachepath;
    }

    public function gifToMP4($gifpath, $target)
    {
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $file = escapeshellarg($gifpath);

        if (!file_exists($target)) { //simple caching.. have to think of something better
            $cmd = "$bin -f gif -y -i $file -c:v libx264 -f mp4 $target";
            system($cmd);
        }


        return $target;
    }

    public function saveAsOGG($source, $target)
    {
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $h265 = "$bin -y -i $source -vcodec libtheora -acodec libvorbis -qp 0 -f ogg $target";
        system($h265);
    }

    public function saveAsWebm($source, $target)
    {
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $webm = "$bin -y -i $source -vcodec libvpx -acodec libvorbis -aq 5 -ac 2 -qmax 25 -f webm $target";
        system($webm);
    }

    public function saveFirstFrameOfMP4($path, $target)
    {
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $file = escapeshellarg($path);
        $cmd = "$bin -y -i $file -vframes 1 -f image2 $target";

        system($cmd);
    }

    //from https://stackoverflow.com/questions/4847752/how-to-get-video-duration-dimension-and-size-in-php
    public function getSizeOfMP4($video): array
    {
        $video = escapeshellarg($video);
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $command = $bin . ' -i ' . $video . ' -vstats 2>&1';
        $output = shell_exec($command);

        $regex_sizes = '/Video: ([^,]*), ([^,]*), (\d{1,4})x(\d{1,4})/';

        $codec  = null;
        $width  = null;
        $height = null;
        $hours  = null;
        $mins   = null;
        $secs   = null;
        $ms     = null;

        if (preg_match($regex_sizes, $output, $regs)) {
            $codec  = $regs[1] ?: null;
            $width  = $regs[3] ?: null;
            $height = $regs[4] ?: null;
        }

        $regex_duration = '/Duration: (\d{1,2}):(\d{1,2}):(\d{1,2}).(\d{1,2})/';

        if (preg_match($regex_duration, $output, $regs)) {
            $hours = $regs[1] ?: null;
            $mins  = $regs[2] ?: null;
            $secs  = $regs[3] ?: null;
            $ms    = $regs[4] ?: null;
        }

        return [
            'codec'  => $codec,
            'width'  => $width,
            'height' => $height,
            'hours'  => $hours,
            'mins'   => $mins,
            'secs'   => $secs,
            'ms'     => $ms,
        ];
    }

    public function oembed($url, $type): array
    {
        $data = $this->getURLInfo($url);
        $rawurl = $url . '/raw';
        switch ($type) {
            case 'json':
                header('Content-Type: application/json');
                return [
                    'version' => '1.0',
                    'type' => 'video',
                    'thumbnail_url' => $url . '/preview',
                    'thumbnail_width' => $data['width'],
                    'thumbnail_height' => $data['height'],
                    'width' => $data['width'],
                    'height' => $data['height'],
                    'title' => 'PictShare',
                    'provider_name' => 'PictShare',
                    'provider_url' => DOMAINPATH,
                    'html' => '<video id="video" poster="' . $url . '/preview' . '" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline>
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
     * @param string|int $size
     *
     * @return array|bool
     */
    public function sizeStringToWidthHeight($size)
    {
        if (!$size || !$this->isSize($size)) {
            return false;
        }

        $newSize = $size;

        if (!is_numeric($size)) {
            $newSize = explode('x', $size);
        }

        if (is_array($newSize)) {
            list($maxWidth, $maxHeight) = $newSize;
        } else {
            $maxWidth  = $newSize;
            $maxHeight = $newSize;
        }

        return [
            'width'  => $maxWidth,
            'height' => $maxHeight,
        ];
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    private function couldThisBeAnImage(string $string): bool
    {
        $len = strlen($string);
        $dot = strpos($string, '.');

        if (!$dot) {
            return false;
        }

        if ($dot <= 10 && (($len - $dot) === 4 || ($len - $dot) === 5)) {
            return true;
        }

        return false;
    }

    private function mayDeleteImages(): bool
    {
        if (!defined('MASTER_DELETE_IP') || !MASTER_DELETE_IP) {
            return false;
        }

        $ip = getUserIP();
        $parts = explode(';', MASTER_DELETE_IP);

        foreach ($parts as $part) {
            if (strpos($part, '/') !== false) {       //it's a CIDR address
                if (cidrMatch($ip, $part)) {
                    return true;
                }
            } elseif (isIP($part)) {                //it's an IP address
                if ($part === $ip) {
                    return true;
                }
            } elseif (gethostbyname($part) === $ip) {  //must be a hostname
                return true;
            }
        }

        return false;
    }

    private function deleteImage($hash)
    {
        // Delete hash from hashes.csv.
        $tmpname = ROOT . DS . 'upload' . DS . 'delete_temp.csv';
        $csv = ROOT . DS . 'upload' . DS . 'hashes.csv';
        $fptemp = fopen($tmpname, 'wb');

        if (($handle = fopen($csv, 'rb')) !== false) {
            while (($line = fgets($handle)) !== false) {
                $data = explode(';', $line);
                if ($hash !== trim($data[1])) {
                    fwrite($fptemp, $line);
                }
            }
        }

        fclose($handle);
        fclose($fptemp);
        unlink($csv);
        rename($tmpname, $csv);

        // Delete from the local filesystem.
        StorageProviderFactory::getStorageProvider(StorageProviderFactory::LOCAL_PROVIDER)
            ->delete($hash);

        // Delete from backblaze if configured.
        if (defined('BACKBLAZE')
            && BACKBLAZE === true
            && defined('BACKBLAZE_AUTODELETE')
            && BACKBLAZE_AUTODELETE === true
        ) {
            StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER)
                ->delete($hash);
        }
    }

    private function isFilter($var): bool
    {
        if (strpos($var, '_')) {
            list($var, $val) = explode('_', $var);

            if (!is_numeric($val)) {
                return false;
            }
        }

        return FilterFactory::isValidFilter($var);
    }

    private function isRotation($var)
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
     * @param string|int $var
     *
     * @return bool
     */
    private function isSize($var): bool
    {
        if (is_numeric($var)) {
            return true;
        }

        $a = explode('x', $var);

        return !(count($a) !== 2 || !is_numeric($a[0]) || !is_numeric($a[1]));
    }

    private function getNewHash($type, $length = 10)
    {
        while (1) {
            $hash = getRandomString($length) . '.' . $type;
            if (!$this->hashExists($hash)) {
                return $hash;
            }
        }

        return null;
    }

    private function hashExists($hash): bool
    {
        return is_dir(ROOT . DS . 'upload' . DS . $hash);
    }

    private function getType($url)
    {
        return $this->isTypeAllowed($this->getTypeOfFile($url));
    }

    private function generateDeleteCodeForImage($hash)
    {
        while (1) {
            $code = getRandomString(32);
            $file = ROOT . DS . 'upload' . DS . 'deletecodes' . DS . $code;

            if (file_exists($file)) {
                continue;
            }

            file_put_contents($file, $hash);

            return $code;
        }

        return null;
    }

    private function deleteCodeExists($code): bool
    {
        if (strpos($code, '_')) {
            $code = substr($code, strpos($code, '_') + 1);
        }

        if (!$code || !ctype_alnum($code)) {
            return false;
        }

        $file = ROOT . DS . 'upload' . DS . 'deletecodes' . DS . $code;

        return file_exists($file);
    }

    private function isThisDeleteCodeForImage($code, $hash, bool $delete = false): bool
    {
        if (strpos($code, '_')) {
            $code = substr($code, strpos($code, '_') + 1);
        }

        if (!$hash || !ctype_alnum($code)) {
            return false;
        }

        $file = ROOT . DS . 'upload' . DS . 'deletecodes' . DS . $code;

        if (!file_exists($file)) {
            return false;
        }

        $rHash  = trim(file_get_contents($file));
        $result = $rHash === $hash;

        if ($delete && $result) {
            unlink($file);
        }

        return $result;
    }

    private function saveSHAOfFile($filePath, $hash)
    {
        $sha_file = ROOT . DS . 'upload' . DS . 'hashes.csv';
        $sha = sha1_file($filePath);
        $fp = fopen($sha_file, 'ab');
        fwrite($fp, "$sha;$hash\n");
        fclose($fp);
    }

    private function isDuplicate($file)
    {
        $sha_file = ROOT . DS . 'upload' . DS . 'hashes.csv';
        $sha = sha1_file($file);
        if (!file_exists($sha_file)) {
            return false;
        }
        $fp = fopen($sha_file, 'rb');
        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if (!$line) {
                continue;
            }
            $sha_upload = substr($line, 0, 40);
            if ($sha_upload === $sha) { //when it's a duplicate return the hash of the original file
                fclose($fp);
                return substr($line, 41);
            }
        }

        fclose($fp);

        return false;
    }

    private function base64ToType($base64_string)
    {
        $data = explode(',', $base64_string);
        $data = $data[1];

        $data = str_replace(' ', '+', $data);
        $data = base64_decode($data);

        $info = getimagesizefromstring($data);

        trigger_error('########## FILETYPE: ' . $info['mime']);

        $f = finfo_open();

        return $this->isTypeAllowed(finfo_buffer($f, $data, FILEINFO_MIME_TYPE));
    }

    private function base64ToImage($base64_string, $output_file, $type)
    {
        $data = explode(',', $base64_string);
        $data = $data[1];

        $data = str_replace(' ', '+', $data);

        $data = base64_decode($data);

        $source = imagecreatefromstring($data);
        switch ($type) {
            case 'jpg':
                imagejpeg($source, $output_file, (defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
                trigger_error('========= SAVING AS ' . $type . ' TO ' . $output_file);
                break;

            case 'png':
                imagefill($source, 0, 0, IMG_COLOR_TRANSPARENT);
                imagesavealpha($source, true);
                imagepng($source, $output_file, (defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                trigger_error('========= SAVING AS ' . $type . ' TO ' . $output_file);
                break;

            case 'gif':
                imagegif($source, $output_file);
                trigger_error('========= SAVING AS ' . $type . ' TO ' . $output_file);
                break;

            default:
                imagefill($source, 0, 0, IMG_COLOR_TRANSPARENT);
                imagesavealpha($source, true);
                imagepng($source, $output_file, (defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                break;
        }

        imagedestroy($source);

        return $type;
    }

    private function getTypeOfHash($hash)
    {
        $base_path = ROOT . DS . 'upload' . DS . $hash . DS;
        $path = $base_path . $hash;

        return $this->isTypeAllowed($this->getTypeOfFile($path));
    }

    private function isProperMP4($filename): bool
    {
        $file = escapeshellarg($filename);
        $tmp = ROOT . DS . 'tmp' . DS . md5(time() + random_int(1, 10000)) . '.' . random_int(1, 10000) . '.log';
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');

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

    private function saveAsMP4($source, $target)
    {
        $bin = escapeshellcmd(ROOT . DS . 'bin' . DS . 'ffmpeg');
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $h265 = "$bin -y -i $source -an -c:v libx264 -qp 0 -f mp4 $target";
        system($h265);
    }
}
