<?php 

/**
 * @Todo:
 *  - Resizing
 *  - Filters
 *  - Conversion gif to mp4
 *  - Conversion jpg,png to webp
 */

class ImageController implements ContentController
{
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('png','bmp','gif','jpg','jpeg','x-png','ico','webp');}

    public function handleUpload($tmpfile,$hash=false)
    {
        $type = exif_imagetype($tmpfile); //http://www.php.net/manual/en/function.exif-imagetype.php
        switch($type)
        {
            case 1: $ext = 'gif';break;   //gif
            case 3: $ext = 'png';break;   // png
            case 6: $ext = 'bmp';break;   // bmp
            case 17: $ext = 'ico';break;  // ico
            case 18: $ext = 'webp';break; // webp

            case 2: //we clean up exif data of JPGs so GPS and other data is removed
                $res = imagecreatefromjpeg($tmpfile);
                imagejpeg($res, $tmpfile, (defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
                $ext = 'jpg';

                $newsha1 = sha1_file($tmpfile);
            break;

            default:
                return array('status'=>'err','reason'=>'Not a valid image');
        }

        if($hash===false)
        {
            $hash = getNewHash($ext,6);
        }
        else
        {
            $hash.='.'.$ext;
            if(isExistingHash($hash))
                return array('status'=>'err','reason'=>'Custom hash already exists');
        }

        if($newsha1)
            addSha1($hash,$newsha1);

        mkdir(ROOT.DS.'data'.DS.$hash);
		$file = ROOT.DS.'data'.DS.$hash.DS.$hash;
		
        copy($tmpfile, $file);
        unlink($tmpfile);

        if(defined('LOG_UPLOADER') && LOG_UPLOADER)
		{
			$fh = fopen(ROOT.DS.'data'.DS.'uploads.txt', 'a');
			fwrite($fh, time().';'.$url.';'.$hash.';'.getUserIP()."\n");
			fclose($fh);
		}
        
        return array('status'=>'ok','hash'=>$hash,'url'=>URL.$hash);
    }

    public function handleHash($hash,$url)
    {
        $path = ROOT.DS.'data'.DS.$hash.DS.$hash;

        $type = getExtensionOfFilename($hash);
        switch($type)
        {
            case 'jpeg':
            case 'jpg': 
                header ("Content-type: image/jpeg");
                readfile($path);
            break;

            case 'png': 
                header ("Content-type: image/png");
                readfile($path);
            break;

            case 'gif': 
                header ("Content-type: image/gif");
                readfile($path);
            break;

            case 'webp': 
                header ("Content-type: image/webp");
                readfile($path);
            break;
        }
    }
}