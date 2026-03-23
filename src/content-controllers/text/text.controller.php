<?php 

class TextController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [
        'text/plain',
        'text/csv',
        'text/tab-separated-values',
    ];
    
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('txt','text','csv');}

    public function handleHash($hash,$url,$path=false)
    {
        $path = getDataDir().DS.$hash.DS.$hash;

        if(in_array('raw',$url))
        {
            header('Content-Type: text/plain; charset=utf-8');
            echo file_get_contents($path);
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
        else {
            $fileSize = filesize($path);
            $memLimit = ini_get('memory_limit');
            $memBytes = (int)$memLimit;
            if (str_ends_with($memLimit, 'G')) $memBytes = (int)$memLimit * 1073741824;
            elseif (str_ends_with($memLimit, 'M')) $memBytes = (int)$memLimit * 1048576;
            elseif (str_ends_with($memLimit, 'K')) $memBytes = (int)$memLimit * 1024;

            if ($memBytes > 0 && $fileSize > $memBytes / 4)
                return renderTemplate('text.html.php', ['hash' => $hash, 'content' => null, 'filesize' => $fileSize]);

            return renderTemplate('text.html.php', ['hash' => $hash, 'content' => htmlentities(file_get_contents($path)), 'filesize' => $fileSize]);
        }
    }

    public function handleUpload($tmpfile,$hash=false,$passthrough=false)
    {
        if($hash===false)
        {
            $hash = getNewHash('txt',6);
        }
        else
        {
            if(!endswith($hash,'.txt'))
                $hash.='.txt';
            if(isExistingHash($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        if($passthrough===false)
            storeFile($tmpfile,$hash,true);
        
        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }

    function getTypeOfText($hash)
    {
        return file_get_contents(getDataDir().DS.$hash.DS.'type');
    }
}