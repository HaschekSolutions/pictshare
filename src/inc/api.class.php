<?php

class API
{
    private $url;
    public function __construct($url)
    {
        $this->url = $url;
    }

    public function act()
    {
        try {
            $this->checkPermissions();
        } catch (Exception $e) {
            return array('status' => 'err', 'reason' => $e->getMessage());
        }

        return match ($this->url[0]) {
            'upload' => $this->upload(),
            'delete' => $this->delete(),
            'info' => $this->info(),
            'debug' => $this->debug(),
            default => array('status' => 'err', 'reason' => 'Unknown API call', 'hint' => 'Check https://github.com/HaschekSolutions/pictshare/blob/master/rtfm/API.md for more information'),
        };
    }


    public function debug()
    {
        $data = array(
            'server_name' => $_SERVER['SERVER_NAME'],
            'server_addr' => $_SERVER['SERVER_ADDR'],
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'remote_port' => $_SERVER['REMOTE_PORT'],
            'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'],
            'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'],
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'],
        );

        return $data;
    }

    public function upload()
    {
        try {
            $this->checkUploaderPermissions();
        } catch (Exception $e) {
            return array('status' => 'err', 'reason' => $e->getMessage());
        }

        //check if uploader has privided a requested hash name 
        if (isset($_REQUEST['hash']))
            $hash = sanatizeString(trim($_REQUEST['hash']));
        else
            $hash = false;


        //check if we should get a file from a remote URL
        if ($_REQUEST['url']) {
            $url = trim(rawurldecode($_REQUEST['url']));
            if (checkURLForPrivateIPRange($url)) {
                addToLog(getUserIP() . " tried to get us to download a file from: " . $url . " but it is in a private IP range");
                return ['status' => 'err', 'reason' => 'Private IP range'];
            }
            if (!$url || !startsWith($url, 'http')) {
                addToLog(getUserIP() . " tried to get us to download a file from: " . $url . " but it is not a valid URL");
                return ['status' => 'err', 'reason' => 'Invalid URL'];
            } else if ($this->remote_filesize($url) * 0.000001 > 20) //@todo: dynamic max size
            {
                addToLog(getUserIP() . " tried to get us to download a file from: " . $url . " but it is too big");
                return ['status' => 'err', 'reason' => 'File too big. 20MB max'];
            }

            $name = basename($url);
            $tmpfile = ROOT . DS . 'tmp' . DS . $name;

            $context = stream_context_create(
                [
                    "http" => [
                        "follow_location" => false,
                    ],
                ]
            );
            file_put_contents($tmpfile, file_get_contents($url, false, $context));

            return $this->handleFile($tmpfile, $hash, $url);
        }
        else if (isset($_REQUEST['base64'])) {
            $data = $_REQUEST['base64'];
            $format = $_REQUEST['format'];

            $tmpfile = ROOT . DS . 'tmp' . DS . md5(rand(0, 10000) . time()) . time();

            $this->base64ToFile($data, $tmpfile);

            return $this->handleFile($tmpfile, $hash);
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file']))
            return ['status' => 'err', 'reason' => 'No file uploaded'];
        if ($_FILES['file']["error"] == UPLOAD_ERR_OK) {
            return $this->handleFile($_FILES['file']["tmp_name"], $hash, $_FILES['file']['name']);
        } else {
            addToLog(getUserIP() . " tried to upload a file with the SHA1: " . $_FILES['file']['name'] . " (" . $_FILES['file']['type'] . ") but the upload failed with error code: " . $_FILES['file']["error"] . " (" . $this->uploadErrorMessage($_FILES['file']["error"]) . ")");
            return ['status' => 'err', 'reason' => 'Upload error: ' . $this->uploadErrorMessage($_FILES['file']["error"])];
        }
    }

    private function handleFile($tmpfile, $hash = false, $originalname = false)
    {
        //get the file type
        $size = filesize($tmpfile);
        $type = $this->getFileMimeType($tmpfile);

        //check for duplicates
        $sha1 = sha1_file($tmpfile);
        $ehash = sha1Exists($sha1);
        if ($ehash && file_exists(getDataDir() . DS . $ehash . DS . $ehash))
            return [
                'status' => 'ok',
                'hash' => $ehash,
                'filetype' => $type,
                'url' => getURL() . $ehash,
                'duplicate' => true,
            ];

        //check naughty list (previously deleted files)
        if ($this->isFileInNaughtyList($sha1)) {
            addToLog(getUserIP() . " tried to upload a file with the SHA1: " . $sha1 . " (" . $type . ", original name:" . $originalname . ") but it was previously deleted and therefore on the naughty list");
            return ['status' => 'err', 'reason' => 'File is in the naughty list'];
        }

        $answer = false;
        foreach (loadAllContentControllers() as $cc) {
            $cc = new $cc();
            if ($cc->mimes && in_array($type, $cc->mimes)) {
                $answer = $cc->handleUpload($tmpfile, $hash);
                break;
            }
        }

        if (!$answer) {
            addToLog(getUserIP() . " tried to upload a file with the SHA1: " . $sha1 . " (" . $type . ", original name:" . $originalname . ") but the file type is not supported");
            return ['status' => 'err', 'reason' => 'Unsupported mime type: ' . $type];
        } else if ($answer['hash'] && $answer['status'] == 'ok') {
            $delcode = getRandomString(32);
            $meta = [
                'mime' => $type,
                'size' => $size,
                'size_human' => renderSize($size),
                'original_filename' => $originalname,
                'hash' => $answer['hash'],
                'sha1' => $sha1,
                'uploaded' => time(),
                'ip' => getUserIP(),
                'useragent' => $_SERVER['HTTP_USER_AGENT'],
                'delete_code' => $delcode,
                'delete_url' => getURL() . 'delete_' . $delcode . '/' . $answer['hash'],
                'remote_port' => $_SERVER['REMOTE_PORT'],
            ];

            addToLog(getUserIP() . " uploaded " . $answer['hash'] . " (" . $type . ")");

            updateMetaData($answer['hash'], $meta);
            addSha1($answer['hash'], $sha1);

            //upload to all configured storage controllers
            storageControllerUpload($answer['hash']);

            return [
                'status' => 'ok',
                'hash' => $answer['hash'],
                'filetype' => $type,
                'url' => getURL() . $answer['hash'],
                'delete_code' => $delcode,
                'delete_url' => getURL() . 'delete_' . $delcode . '/' . $answer['hash'],
            ];
        } else {
            addToLog(getUserIP() . " tried to upload a file with the SHA1: " . $sha1 . " (" . $type . ", original name:" . $originalname . ") but the upload failed. Probably in the handleUpload method of the content controller " . get_class($cc));
            return ['status' => 'err', 'reason' => 'Strange error during upload'];
        }
    }



    public function checkPermissions()
    {
        // check write permissions first
        if (!isFolderWritable(getDataDir()))
            throw new Exception('Data directory not writable');
        else if (!isFolderWritable(ROOT . DS . 'tmp'))
            throw new Exception('Temp directory not writable');
    }

    public function checkUploaderPermissions()
    {
        if (defined('ALLOWED_SUBNET') && ALLOWED_SUBNET != '' && !isIPInRange(getUserIP(), ALLOWED_SUBNET)) {
            throw new Exception('Access denied');
        } else if (defined('UPLOAD_CODE') && UPLOAD_CODE != '') {
            if (!isset($_REQUEST['uploadcode']) || $_REQUEST['uploadcode'] != UPLOAD_CODE) {
                throw new Exception('Incorrect upload code specified - Access denied');
            }
        }
    }

    public function getFileMimeType($file)
    {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            return $finfo->file($file);
        } catch (Exception $e) {
            //fallback to shell command if finfo is not available
            $mimeType = shell_exec('file --mime-type -b ' . escapeshellarg($file));
            return trim($mimeType);
        }
    }

    public function isFileInNaughtyList($sha1)
    {
        $naughtyList = getDataDir() . DS . 'naughty.csv';
        if (!file_exists($naughtyList)) touch($naughtyList);
        $handle = fopen($naughtyList, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (substr($line, 0, 40) === $sha1) return true;
            }

            fclose($handle);
        }
        return false;
    }

    public function uploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                return 'There is no error, the file uploaded successfully.';
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }

    function remote_filesize($url) {
        static $regex = '/^Content-Length: *+\K\d++$/im';
        if (!$fp = @fopen($url, 'rb'))
            return false;
        if (
            isset($http_response_header) &&
            preg_match($regex, implode("\n", $http_response_header), $matches)
        )
            return (int)$matches[0];
        return strlen(stream_get_contents($fp));
    }

    function base64ToFile($base64_string, $output_file)
    {
        $data = explode(',', $base64_string);
        $data = $data[1];
        $data = str_replace(' ','+',$data);
        $data = base64_decode($data);
        $ifp = fopen( $output_file, 'wb' ); 
        fwrite( $ifp, $data );
        fclose( $ifp ); 
    }
}
