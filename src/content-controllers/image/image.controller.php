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
    public const ctype = 'static';
    public $mimes = array(
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/bmp',
        'image/webp',
        'image/x-icon'
    );
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('png','bmp','gif','jpg','jpeg','x-png','webp');}
    

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

            case 2:
                //we clean up exif data of JPGs so GPS and other data is removed
                $res = imagecreatefromjpeg($tmpfile);

                // rotate based on EXIF Orientation
                $exif = exif_read_data($tmpfile);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 2:
                            imageflip($res, IMG_FLIP_HORIZONTAL);
                        case 1:
                            // Nothing to do
                            break;

                        case 4:
                            imageflip($res, IMG_FLIP_HORIZONTAL);
                            // Also rotate
                        case 3:
                            $res = imagerotate($res, 180, 0);
                            break;

                        case 5:
                            imageflip($res, IMG_FLIP_VERTICAL);
                            // Also rotate
                        case 6:
                            $res = imagerotate($res, -90, 0);
                            break;

                        case 7:
                            imageflip($res, IMG_FLIP_VERTICAL);
                            // Also rotate
                        case 8:
                            $res = imagerotate($res, 90, 0);
                            break;
                    }
                }

                imagejpeg($res, $tmpfile, (defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
                $ext = 'jpg';
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
            if(!endswith($hash,'.'.$ext))
                $hash.='.'.$ext;
            if(isExistingHash($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        storeFile($tmpfile,$hash,true);
        
        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }

    public function handleHash($hash,$url)
    {
        $path = getDataDir().DS.$hash.DS.$hash;
        $type = getExtensionOfFilename($hash);

        //get all our sub files where all the good functions lie
        include_once(dirname(__FILE__).DS.'resize.php');
        include_once(dirname(__FILE__).DS.'filters.php');
        include_once(dirname(__FILE__).DS.'conversion.php');

        //don't do this if it's a gif because PHP can't handle animated gifs
        if($type!='gif')
        {
            $filters = getFilters();
            foreach($url as $u)
            {
                if(isSize($u))
                    $modifiers['size'] = $u;
                else if(isRotation($u))
                    $modifiers['rotation'] = $u;
                else // check for filters
                {
                    foreach($filters as $filter)
                    {
                        if(startsWith($u,$filter) && ($u==$filter || startsWith($u,$filter.'_')))
                        {
                            $a = explode('_',$u);
                            $value = $a[1];
                            if(is_numeric($value))
                                $modifiers['filters'][] = array('filter'=>$filter,'value'=>$value);
                            else
                                $modifiers['filters'][] = array('filter'=>$filter);
                        }
                    }
                }
            }

            if( (in_array('webp',$url) && $type!='webp') || ( $this->shouldAlwaysBeWebp() && ($type=='jpg' || $type=='png') ) )
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
            $newpath = getDataDir().DS.$hash.DS.$modhash.'_'.$hash;
            $im = $this->getObjOfImage($path);
            $f = new Filter();

            if(!file_exists($newpath))
            {
                foreach($modifiers as $mod => $val)
                {
                    switch($mod)
                    {
                        case 'filters':
                            foreach($val as $fd)
                            {
                                $filter = $fd['filter'];
                                $value = $fd['value'];
                                $im = $f->$filter($im,$value);
                            }
                        break;

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
                            $mp4path = getDataDir().DS.$hash.DS.$hash.'mp4';
                            if(!file_exists($mp4path))
                                $this->gifToMP4($path,$mp4path);
                            $path = $mp4path;
                            
                                if(in_array('raw',$url))
                                (new VideoController())->serveMP4($path,$hash);
                                else if(in_array('preview',$url))
                                {
                                    $preview = $path.'_preview.jpg';
                                    if(!file_exists($preview))
                                    {
                                        (new VideoController())->saveFirstFrameOfMP4($path,$preview);
                                    }
                        
                                    header ("Content-type: image/jpeg");
                                    readfile($preview);
                                    exit;
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
                                {
                                    $data = array('url'=>implode('/',$url),'hash'=>$hash,'filesize'=>renderSize(filesize($path)));
                                    return renderTemplate('video.html.php',$data);
                                    
                                }
                        break;
                    }
                }

                $this->saveObjOfImage($im,$newpath,$type);
            }
            else if($modifiers['webp'])
            {
                $type = 'webp';
            }
            $path = $newpath;
            
        }
        
        switch($type)
        {
            case 'jpeg':
            case 'jpg': 
                header ("Content-type: image/jpeg");
                header ("Last-Modified: ".gmdate('D, d M Y H:i:s ', filemtime($path)) . 'GMT');
                header ("ETag: $hash");
                header('Cache-control: public, max-age=31536000');
                readfile($path);
            break;

            case 'png': 
                header ("Content-type: image/png");
                header ("Last-Modified: ".gmdate('D, d M Y H:i:s ', filemtime($path)) . 'GMT');
                header ("ETag: $hash");
                header('Cache-control: public, max-age=31536000');
                readfile($path);
            break;

            case 'gif': 
                header ("Content-type: image/gif");
                header ("Last-Modified: ".gmdate('D, d M Y H:i:s ', filemtime($path)) . 'GMT');
                header ("ETag: $hash");
                header('Cache-control: public, max-age=31536000');
                readfile($path);
            break;

            case 'webp': 
                header ("Content-type: image/webp");
                header ("Last-Modified: ".gmdate('D, d M Y H:i:s ', filemtime($path)) . 'GMT');
                header ("ETag: $hash");
                header('Cache-control: public, max-age=31536000');
                readfile($path);
            break;
        }
    }

    function getObjOfImage($path)
    {
        return imagecreatefromstring(file_get_contents($path));
    }

    function gifToMP4($gifpath,$target)
	{
		$bin = escapeshellcmd(FFMPEG_BINARY);
		$file = escapeshellarg($gifpath);
		
		if(!file_exists($target)) //simple caching.. have to think of something better
		{
			$cmd = "$bin -f gif -y -i $file -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" -f mp4 $target";
			system($cmd);
		}
		
		return $target;
	}

    function saveObjOfImage($im,$path,$type)
    {
        $tmppath = '/tmp/'.getNewHash($type,12);
        switch($type)
        {
            case 'jpeg':
            case 'jpg': 
                imagejpeg($im,$tmppath,(defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
            break;

            case 'png': 
                imagepng($im,$tmppath,(defined('PNG_COMPRESSION')?PNG_COMPRESSION:6));
            break;

            case 'webp': 
                imagepalettetotruecolor($im);
                imagealphablending($im, true);
                imagewebp($im,$tmppath,(defined('WEBP_COMPRESSION')?WEBP_COMPRESSION:80));
            break;
        }

        if(file_exists($tmppath) && filesize($tmppath)>0)
        {
            rename($tmppath,$path);
            return $im;
        }
        else
        {
            return false;
        }

        
    }

    function shouldAlwaysBeWebp()
    {
        //sanity check
        if(!$_SERVER['HTTP_ACCEPT']) return false;

        if(defined('ALWAYS_WEBP') && ALWAYS_WEBP && strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false )
            return true;
        else
        return false;
    }
}