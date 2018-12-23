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

        //get all our sub files where all the good functions lie
        include_once(dirname(__FILE__).DS.'resize.php');
        include_once(dirname(__FILE__).DS.'filters.php');
        include_once(dirname(__FILE__).DS.'conversion.php');

        //don't do this if it's a gif because PHP can't handle animated gifs
        if($type!='gif')
        {
            foreach($url as $u)
            {
                if(isSize($u))
                    $modifiers['size'] = $u;
                else if(isRotation($u))
                    $modifiers['rotation'] = $u;
            }
            if(in_array('webp',$url) && $type!='webp')
                $modifiers['webp'] = true;
            if(in_array('forcesize',$url) && $modifiers['size'])
                $modifiers['forcesize'] = true;
        }
        else //gif
        {
            if(in_array('mp4',$url))
                $modifiers['mp4']=true;
        }

        if($modifiers)
        {
            //why in gods name would you use http build query here???
            //well we want a unique filename for every modied image
            //so if we take all parameters in key=>value form and hash it
            //we get one nice little hash for every eventuality
            $modhash = md5(http_build_query($modifiers,'',','));
            $newpath = ROOT.DS.'data'.DS.$hash.DS.$modhash.'_'.$hash;
            $im = $this->getObjOfImage($path);

            if(!file_exists($newpath))
            {
                foreach($modifiers as $mod => $val)
                {
                    switch($mod)
                    {
                        case 'size':
                            ($modifiers['forcesize']?forceResize($im,$val):resize($im,$val));
                        break;

                        case 'rotation':
                            rotate($im,$val);
                        break;

                        case 'webp':
                            $type = 'webp';
                        break;

                        case 'mp4':
                            
                        break;
                    }
                }

                $this->saveObjOfImage($im,$newpath,$type);
            }
            $path = $newpath;
            
        }

        
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

    function getObjOfImage($path)
    {
        return imagecreatefromstring(file_get_contents($path));
    }

    function saveObjOfImage($im,$path,$type)
    {
        switch($type)
        {
            case 'jpeg':
            case 'jpg': 
                imagejpeg($im,$path,(defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
            break;

            case 'png': 
                imagepng($im,$path,(defined('PNG_COMPRESSION')?PNG_COMPRESSION:6));
            break;

            case 'webp': 
                imagewebp($im,$path,(defined('WEBP_COMPRESSION')?WEBP_COMPRESSION:80));
            break;
        }

        return $im;
    }
}