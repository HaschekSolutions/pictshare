<?php

declare(strict_types=1);

namespace PictShare\Controllers;

use PictShare\Classes\Configuration;
use PictShare\Classes\FileSizeFormatter;
use PictShare\Classes\FilterFactory;
use PictShare\Classes\StorageProviderFactory;
use PictShare\Models\BaseModel;

abstract class AbstractController
{
    /**
     * @var BaseModel
     */
    protected $model;


    final public function __construct()
    {
        $this->model = new BaseModel();
    }

    /**
     * Strip slashes from globals.
     */
    protected function removeMagicQuotes()
    {
        if (get_magic_quotes_gpc()) {
            $_GET  = $this->stripSlashesDeep($_GET);
            $_POST = $this->stripSlashesDeep($_POST);
        }
    }

    /**
     * @param string $url
     * @param bool $isPath
     *
     * @return array
     */
    protected function getURLInfo(string $url, bool $isPath = false): array
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

        $path = BASE_DIR . $hash . '/' . $file;
        if (!file_exists($path)) {
            $path = BASE_DIR . $hash . '/' . $hash;
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

    /**
     * @param string|null $urlIn
     *
     * @return array
     */
    protected function urlToData(string $urlIn = null): array
    {
        $urlIn = $urlIn ?? '';
        $url  = explode('/', $urlIn);
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

            if ($this->model->isImage($el)) {
                //if there are more than one hashes in url
                //make an album from them
                if ($data['hash']) {
                    if (!$data['album']) {
                        $data['album'][] = $data['hash'];
                    }
                    $data['album'][] = $el;
                }
                $data['hash'] = $el;
            } elseif (Configuration::isBackblazeAutoDownloadEnabled() && $this->couldThisBeAnImage($el)) {
                // Looks like it might be a hash but didn't find it here. Let's see.
                $localProvider = StorageProviderFactory::getStorageProvider(StorageProviderFactory::LOCAL_PROVIDER);

                if (!$localProvider->fileExists($el)) {
                    $fileContent = StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER)
                        ->get($el, $el);

                    if ($fileContent) { // If the backblaze get function says it's an image, we'll take it.
                        $localProvider->save($el, $el, $fileContent);

                        $data['hash'] = $el;
                    }
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

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getCacheName(array $data): string
    {
        ksort($data);
        unset($data['raw']);

        $name = [];

        foreach ($data as $key => $val) {
            if ($key !== 'hash') {
                if (!\is_array($val)) {
                    $name[] = $key . '_' . $val;
                } else {
                    foreach ($val as $valdata) {
                        $name[] = $valdata;
                    }
                }
            }
        }

        if (\count($name) > 0) {
            $name = implode('.', $name);
        }

        return ($name ? $name . '.' : '') . $data['hash'];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    protected function uploadCodeExists(string $code): bool
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

    /**
     * @param int $index
     * @param string $params
     *
     * @return mixed
     */
    protected function translate(int $index, string $params = '')
    {
        $lang  = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $words = [];

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

    /**
     * @param string $url
     *
     * @return array
     */
    protected function uploadImageFromURL(string $url): array
    {
        $type = $this->getTypeOfFile($url);
        $type = $this->model->isTypeAllowed($type);

        if (!$type) {
            return [
                'status' => 'ERR',
                'reason' => 'wrong filetype',
            ];
        }

        $randomNumber = random_int(1, 999) * random_int(0, 10000) + time();

        $tempfile = BASE_DIR . 'tmp/' . md5((string) $randomNumber);
        file_put_contents($tempfile, file_get_contents($url));

        //remove all exif data from jpeg
        if ($type === 'jpg') {
            $res = imagecreatefromjpeg($tempfile);
            imagejpeg($res, $tempfile, (\defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
        }
        $url = $tempfile;

        $dup_id = $this->isDuplicate($url);
        if ($dup_id) {
            $hash = $dup_id;
            $url = BASE_DIR . $hash . '/' . $hash;
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
            system('nohup php ' . BASE_DIR . 'tools/re-encode_mp4.php force ' . $hash . ' > /dev/null 2> /dev/null &');
        }

        if (LOG_UPLOADER) {
            $fh = fopen(UPLOAD_DIR . 'uploads.txt', 'ab');
            fwrite($fh, time() . ';' . $url . ';' . $hash . ';' . $this->getUserIP() . "\n");
            fclose($fh);
        }

        if (Configuration::isBackblazeAutoUploadEnabled()) {
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

    /**
     * @see From https://stackoverflow.com/questions/4847752/how-to-get-video-duration-dimension-and-size-in-php
     *
     * @param string $video
     *
     * @return array
     */
    protected function getSizeOfMP4(string $video): array
    {
        $video = escapeshellarg($video);
        $bin = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
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

    /**
     * @param string $type
     * @param int $length
     *
     * @return null|string
     */
    protected function getNewHash(string $type, int $length = 10)
    {
        while (1) {
            $hash = $this->getRandomString($length) . '.' . $type;
            if (!$this->model->hashExists($hash)) {
                return $hash;
            }
        }

        return null;
    }

    /**
     * @param string|int $var
     *
     * @return bool
     */
    protected function isSize($var): bool
    {
        if (is_numeric($var)) {
            return true;
        }

        $a = explode('x', $var);

        return !(count($a) !== 2 || !is_numeric($a[0]) || !is_numeric($a[1]));
    }

    /**
     * @param string $url
     *
     * @return bool|string
     */
    protected function getTypeOfFile(string $url)
    {
        $fi = new \finfo(FILEINFO_MIME);
        $type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));

        //to catch a strange error for PHP7 and Alpine Linux
        //if the file seems to be a stream, use unix file command
        if ($this->startsWith($type, 'application/octet-stream') && stripos(strtoupper(PHP_OS), 'WIN') !== 0) {
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

    /**
     * @param array|string $value
     *
     * @return array|string
     */
    private function stripSlashesDeep($value)
    {
        return \is_array($value)
            ? array_map('stripSlashesDeep', $value)
            : stripslashes($value);
    }

    /**
     * @param string $url
     *
     * @return bool|string
     */
    private function getType(string $url)
    {
        return $this->model->isTypeAllowed($this->getTypeOfFile($url));
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

    /**
     * @return bool
     */
    private function mayDeleteImages(): bool
    {
        if (!\defined('MASTER_DELETE_IP') || !MASTER_DELETE_IP) {
            return false;
        }

        $ip = $this->getUserIP();
        $parts = explode(';', MASTER_DELETE_IP);

        foreach ($parts as $part) {
            if (strpos($part, '/') !== false) {       //it's a CIDR address
                if ($this->cidrMatch($ip, $part)) {
                    return true;
                }
            } elseif ($this->isIP($part)) {                //it's an IP address
                if ($part === $ip) {
                    return true;
                }
            } elseif (gethostbyname($part) === $ip) {  //must be a hostname
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $hash
     */
    private function deleteImage(string $hash)
    {
        // Delete hash from hashes.csv.
        $tmpname = UPLOAD_DIR . 'delete_temp.csv';
        $csv = UPLOAD_DIR . 'hashes.csv';
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
        if (Configuration::isBackblazeAutoDeleteEnabled()) {
            StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER)
                ->delete($hash);
        }
    }

    /**
     * @param string $var
     *
     * @return bool
     */
    private function isFilter(string $var): bool
    {
        if (strpos($var, '_')) {
            list($var, $val) = explode('_', $var);

            if (!is_numeric($val)) {
                return false;
            }
        }

        return FilterFactory::isValidFilter($var);
    }

    /**
     * @param string $var
     *
     * @return bool
     */
    private function isRotation(string $var)
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
     * @param string $hash
     *
     * @return null|string
     */
    private function generateDeleteCodeForImage(string $hash)
    {
        while (1) {
            $code = $this->getRandomString(32);
            $file = UPLOAD_DIR . 'deletecodes/' . $code;

            if (file_exists($file)) {
                continue;
            }

            file_put_contents($file, $hash);

            return $code;
        }

        return null;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    private function deleteCodeExists(string $code): bool
    {
        if (strpos($code, '_')) {
            $code = substr($code, strpos($code, '_') + 1);
        }

        if (!$code || !ctype_alnum($code)) {
            return false;
        }

        $file = UPLOAD_DIR . 'deletecodes/' . $code;

        return file_exists($file);
    }

    /**
     * @param string $code
     * @param string $hash
     * @param bool $delete
     *
     * @return bool
     */
    private function isThisDeleteCodeForImage(string $code, string $hash, bool $delete = false): bool
    {
        if (strpos($code, '_')) {
            $code = substr($code, strpos($code, '_') + 1);
        }

        if (!$hash || !ctype_alnum($code)) {
            return false;
        }

        $file = UPLOAD_DIR . 'deletecodes/' . $code;

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

    private function saveSHAOfFile(string $filePath, string $hash)
    {
        $sha_file = UPLOAD_DIR . 'hashes.csv';
        $sha = sha1_file($filePath);
        $fp = fopen($sha_file, 'ab');
        fwrite($fp, "$sha;$hash\n");
        fclose($fp);
    }

    /**
     * @param string $file
     *
     * @return bool|string
     */
    private function isDuplicate(string $file)
    {
        $sha_file = UPLOAD_DIR . 'hashes.csv';
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

    /**
     * @param string $hash
     *
     * @return bool|string
     */
    private function getTypeOfHash(string $hash)
    {
        return $this->model->isTypeAllowed($this->getTypeOfFile(UPLOAD_DIR . $hash . '/' . $hash));
    }

    /**
     * @return string
     * @return string
     */
    private function getUserIP(): string
    {
        $client  = filter_input(INPUT_SERVER, 'HTTP_CLIENT_IP', FILTER_VALIDATE_IP);
        $forward = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_VALIDATE_IP);
        $remote  = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING);

        if (strpos($forward, ',')) {
            $a = explode(',', $forward);
            $forward = trim($a[0]);
        }

        if ($client) {
            return $client;
        }

        if ($forward) {
            return $forward;
        }

        return $remote;
    }

    /**
     * @param string $ip
     * @param string $range
     *
     * @return bool
     */
    private function cidrMatch(string $ip, string $range): bool
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
    private function isIP(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * @param int $length
     * @param string $keySpace
     *
     * @return string
     */
    private function getRandomString(int $length = 32, string $keySpace = '0123456789abcdefghijklmnopqrstuvwxyz'): string
    {
        $str = '';
        $max = mb_strlen($keySpace, '8bit') - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[random_int(0, $max)];
        }

        return $str;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    private function isProperMP4(string $filename): bool
    {
        $file         = escapeshellarg($filename);
        $randomNumber = time() + random_int(1, 10000);
        $tmp          = BASE_DIR . 'tmp/' . md5((string) $randomNumber) . '.' . random_int(1, 10000) . '.log';
        $bin          = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $cmd          = "$bin -i $file > $tmp 2>> $tmp";

        system($cmd);

        $answer = file($tmp);
        unlink($tmp);
        $ismp4 = false;
        if (\is_array($answer)) {
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
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}
