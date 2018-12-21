<?php 

class TextController
{
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('txt');}

    public function handleHash($hash,$url)
    {
        $path = ROOT.DS.'data'.DS.$hash.DS.$hash;

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
                readfile($path);
                exit;
            }
        }
        else
            renderTemplate('text',array('hash'=>$hash,'content'=>htmlentities(file_get_contents($path))));
    }

    public function handleUpload($tmpfile,$hash=false)
    {
        if($hash===false)
        {
            $hash = getNewHash('txt',6);
        }

        mkdir(ROOT.DS.'data'.DS.$hash);
		$file = ROOT.DS.'data'.DS.$hash.DS.$hash;
		
        move_uploaded_file($tmpfile, $file);

        if(defined('ALT_FOLDER') && ALT_FOLDER)
        {
            $altname=ALT_FOLDER.DS.$hash;
            if(!file_exists($altname) && is_dir(ALT_FOLDER))
            {
                copy($file,$altname);
            }
        }

        if(defined('LOG_UPLOADER') && LOG_UPLOADER)
		{
			$fh = fopen(ROOT.DS.'data'.DS.'uploads.txt', 'a');
			fwrite($fh, time().';'.$url.';'.$hash.';'.getUserIP()."\n");
			fclose($fh);
		}
        
        return array('status'=>'ok','hash'=>$hash,'url'=>URL.$hash);
    }

    function getTypeOfText($hash)
    {
        return file_get_contents(ROOT.DS.'data'.DS.$hash.DS.'type');
    }
}