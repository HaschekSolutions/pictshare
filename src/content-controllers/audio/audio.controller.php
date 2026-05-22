<?php 

class AudioController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [
        'audio/mpeg',
        'audio/ogg',
        'audio/wav',
        'audio/x-wav',
        'audio/flac',
        'audio/x-m4a',
        'audio/mp4',
    ];

    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('mp3', 'wav', 'ogg', 'flac', 'm4a');}

    public function handleHash($hash,$url,$path=false)
    {
        if($path===false)
            $path = getDataDir().DS.$hash.DS.$hash;

        $extension = pathinfo($hash, PATHINFO_EXTENSION);
        
        if(in_array('raw',$url))
        {
            $this->serveAudio($path, $extension);
        }
        else if(in_array('download',$url))
        {
            if (file_exists($path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($path).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                serveFile($path);
                exit;
            }
        }
        else
        {
            $data = array(
                'url' => implode('/', $url),
                'hash' => $hash,
                'filesize' => renderSize(filesize($path)),
                'extension' => $extension,
                'slogan' => (defined('TITLE')?TITLE:'PictShare Audio Player')
            );
            return renderTemplate('audio.html.php', $data);
        }
    }

    public function handleUpload($tmpfile,$hash=false,$passthrough=false)
    {
        $extension = pathinfo($tmpfile, PATHINFO_EXTENSION);
        if (!$extension) {
            // Try to guess from mime type if extension is missing from tmpfile
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpfile);
            switch($mime) {
                case 'audio/mpeg': $extension = 'mp3'; break;
                case 'audio/ogg': $extension = 'ogg'; break;
                case 'audio/wav':
                case 'audio/x-wav': $extension = 'wav'; break;
                case 'audio/flac': $extension = 'flac'; break;
                case 'audio/mp4':
                case 'audio/x-m4a': $extension = 'm4a'; break;
                default: $extension = 'mp3'; // Fallback
            }
        }

        if($hash===false)
            $hash = getNewHash($extension, 6);
        else
        {
            if(!endswith($hash,'.'.$extension))
                $hash.='.'.$extension;
                
            if(isExistingHash($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        if($passthrough===false)
        {
            storeFile($tmpfile, $hash, true);
        }
        
        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }

    function serveAudio($path, $extension)
    {
        switch($extension) {
            case 'mp3': header('Content-type: audio/mpeg'); break;
            case 'ogg': header('Content-type: audio/ogg'); break;
            case 'wav': header('Content-type: audio/wav'); break;
            case 'flac': header('Content-type: audio/flac'); break;
            case 'm4a': header('Content-type: audio/mp4'); break;
            default: header('Content-type: application/octet-stream');
        }
        
        header ("Last-Modified: ".gmdate('D, d M Y H:i:s ', filemtime($path)) . 'GMT');
        header ("ETag: ".sha1_file($path));
        header('Cache-control: public, max-age=31536000');
        serveFile($path);
        exit();
    }
}