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


    public function debug(){
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

        if (!isset($_FILES['file']) || !is_array($_FILES['file']))
            return ['status' => 'err', 'reason' => 'No file uploaded'];

        if ($_FILES['file']["error"] == UPLOAD_ERR_OK) {
            //get the file type
            $type = $this->getFileMimeType($_FILES['file']["tmp_name"]);

            //check for duplicates
            $sha1 = sha1_file($_FILES['file']["tmp_name"]);
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
            if ($this->isFileInNaughtyList($sha1))
                return ['status' => 'err', 'reason' => 'File is in the naughty list'];

            $answer = false;
            foreach(loadAllContentControllers() as $cc)
            {
                $cc = new $cc();
                if ($cc->mimes && in_array($type, $cc->mimes))
                {
                    $answer = $cc->handleUpload($_FILES['file']['tmp_name'],$hash);
                    break;
                }
            }

            if (!$answer)
                return ['status' => 'err', 'reason' => 'Unsupported mime type: ' . $type];
            else if($answer['hash'] && $answer['status']=='ok'){
                $delcode = getRandomString(32);
                $meta = [
                    'mime' => $type,
                    'size' => $_FILES['file']['size'],
                    'original_filename' => $_FILES['file']['name'],
                    'hash' => $answer['hash'],
                    'sha1' => $sha1,
                    'uploaded' => time(),
                    'ip' => getUserIP(),
                    'useragent' => $_SERVER['HTTP_USER_AGENT'],
                    'delete_code' => $delcode,
                    'delete_url' => getURL().'delete_'.$delcode.'/'.$answer['hash'],
                    'remote_port' => $_SERVER['REMOTE_PORT'],
                ];

                addToLog(getUserIP()." uploaded ".$answer['hash']." (".$type.")");

                updateMetaData($answer['hash'], $meta);
                addSha1($answer['hash'],$sha1);

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
            }
            else
                return ['status' => 'err', 'reason' => 'Strange error during upload'];
        } else
            return ['status' => 'err', 'reason' => 'Upload error: '.$this->uploadErrorMessage($_FILES['file']["error"])];
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

    public function uploadErrorMessage(int $errorCode): string {
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
}
