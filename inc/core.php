<?php

function __autoload($className)
{
	if (file_exists(ROOT . DS . 'models' . DS . strtolower($className) . '.php'))
		require_once(ROOT . DS . 'models' . DS . strtolower($className) . '.php');
    if (file_exists(ROOT . DS . 'classes' . DS . strtolower($className) . '.php'))
		require_once(ROOT . DS . 'classes' . DS . strtolower($className) . '.php');
}

function stripSlashesDeep($value)
{
	$value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
	return $value;
}

function removeMagicQuotes()
{
    if ( get_magic_quotes_gpc() )
    {
            $_GET    = stripSlashesDeep($_GET   );
            $_POST   = stripSlashesDeep($_POST  );
            $_COOKIE = stripSlashesDeep($_COOKIE);
    }
}



function aasort (&$array, $key)
{
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}



function callHook()
{
    global $url;
    
    whatToDo($url);
}

function whatToDo($url)
{
    $pm = new PictshareModel();
    
    
    $data = $pm->urlToData($url);
    
    if(!is_array($data) || !$data['hash'])
    {
        if((UPLOAD_FORM_LOCATION && $url==UPLOAD_FORM_LOCATION) || (!UPLOAD_FORM_LOCATION && $url='/'))
        {
            $upload_answer = $pm->ProcessUploads();
            if($upload_answer)
                $o=$upload_answer;
            else
                $o.= $pm->renderUploadForm();
            
            $vars['content'] = $o;
            $vars['slogan'] = $pm->translate(2);
            
        }
        
        if(!$vars && LOW_PROFILE)
        {
            header('HTTP/1.0 404 Not Found');
            exit();
        }
        else if(!$vars)
        {
            $vars['content'] = $pm->translate(12);
            $vars['slogan'] = $pm->translate(2);
        }

        render($vars);
    }
    else
        renderImage($data);
}



function renderImage($data)
{
    $hash = $data['hash'];
    if($data['changecode'])
    {
        $changecode = $data['changecode'];
        unset($data['changecode']);
    }
    
    
        
    $pm = new PictshareModel();
    $base_path = ROOT.DS.'upload'.DS.$hash.DS;
    $path = $base_path.$hash;
    $type = $pm->isTypeAllowed($pm->getTypeOfFile($path));
    $cached = false;
    
    $cachename = $pm->getCacheName($data);
    $cachepath = $base_path.$cachename;
    if(file_exists($cachepath))
    {
        $path = $cachepath;
        $cached = true;
    }
    else if(MAX_RESIZED_IMAGES > -1 && $pm->countResizedImages($hash)>MAX_RESIZED_IMAGES) //if the number of max resized images is reached, just show the real one
        $path = ROOT.DS.'upload'.DS.$hash.DS.$hash;
    
    switch($type)
    {
        case 'jpg': 
            header ("Content-type: image/jpeg");
            $im = imagecreatefromjpeg($path);
            if(!$cached)
            {
                if($pm->changeCodeExists($changecode))
                {
                    changeImage($im,$data);
                    imagejpeg($im,$cachepath,95);
                }
                    
            }
            imagejpeg($im);
        break;
        case 'png': 
            header ("Content-type: image/png");
            $im = imagecreatefrompng($path);
            if(!$cached)
            {
                if($pm->changeCodeExists($changecode))
                {
                    changeImage($im,$data);
                    imagepng($im,$cachepath,1);
                }
            }
            imageAlphaBlending($im, true);
            imageSaveAlpha($im, true);
            imagepng($im);
        break;
        case 'gif': 
            if($data['mp4'])
            {
                header("Content-Type: video/mp4");
                readfile($pm->gifToMP4($path));
            }
            else
            {
                header ("Content-type: image/gif");
                readfile($path);
            }
                
        break;
        case 'mp4':
            if(!$cached)
            {
            	$pm->resizeMP4($data,$cachepath);
                $path = $cachepath;
            }

            if(filesize($path)==0) //if there was an error and the file is 0 bytes, use the original
                $path = ROOT.DS.'upload'.DS.$hash.DS.$hash;
            
            if($data['raw'])
            {
                serveFile($path, '/raw/'.$hash,'video/mp4');
            }
            else if($data['preview'])
            {
                $file = $path.'.jpg';
                if(!file_exists($file))
                    $pm->saveFirstFrameOfMP4($path);
                header ("Content-type: image/jpeg");
                readfile($file);
            }
            else
                renderMP4($path,$data);
        break;
    }
    
    exit();
}

function changeImage(&$im,$data)
{
    $image = new Image();
    foreach($data as $action=>$val)
    {
        switch($action)
        {
            case 'rotate': $image->rotate($im,$val);break; 
            case 'size': (($data['forcesize']===true)?$image->forceResize($im,$val):$image->resize($im,$val));break;
            case 'filter': $image->filter($im,$val);break;
        }
    }
}



function render($variables=null)
{
    if(is_array($variables))
        extract($variables);
    include (ROOT . DS . 'template.php');
}

function renderMP4($path,$data)
{
    $pm = new PictshareModel;
    $hash = $data['hash'];
    if($data['size'])
        $hash = $data['size'].'/'.$hash;
    $info = $pm->getSizeOfMP4($path);
    $width = $info['width'];
    $height = $info['height'];
    include (ROOT . DS . 'template_mp4.php');
}

//
// from: https://stackoverflow.com/questions/25975943/php-serve-mp4-chrome-provisional-headers-are-shown-request-is-not-finished-ye
//
function serveFile($filename, $filename_output = false, $mime = 'application/octet-stream')
{
    $buffer_size = 8192;
    $expiry = 90; //days

    if(!file_exists($filename))
    {
        throw new Exception('File not found: ' . $filename);
    }
    if(!is_readable($filename))
    {
        throw new Exception('File not readable: ' . $filename);
    }

    header_remove('Cache-Control');
    header_remove('Pragma');

    $byte_offset = 0;
    $filesize_bytes = $filesize_original = filesize($filename);

    header('Accept-Ranges: bytes', true);
    header('Content-Type: ' . $mime, true);

    if($filename_output)
    {
        header('Content-Disposition: attachment; filename="' . $filename_output . '"');
    }

    // Content-Range header for byte offsets
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('%bytes=(\d+)-(\d+)?%i', $_SERVER['HTTP_RANGE'], $match))
    {
        $byte_offset = (int) $match[1];//Offset signifies where we should begin to read the file            
        if (isset($match[2]))//Length is for how long we should read the file according to the browser, and can never go beyond the file size
        {
            $filesize_bytes = min((int) $match[2], $filesize_bytes - $byte_offset);
        }
        header("HTTP/1.1 206 Partial content");
        header(sprintf('Content-Range: bytes %d-%d/%d', $byte_offset, $filesize_bytes - 1, $filesize_original)); ### Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent
    }

    $byte_range = $filesize_bytes - $byte_offset;

    header('Content-Length: ' . $byte_range);
    header('Expires: ' . date('D, d M Y H:i:s', time() + 60 * 60 * 24 * $expiry) . ' GMT');

    $buffer = '';
    $bytes_remaining = $byte_range;

    $handle = fopen($filename, 'r');
    if(!$handle)
    {
        throw new Exception("Could not get handle for file: " .  $filename);
    }
    if (fseek($handle, $byte_offset, SEEK_SET) == -1)
    {
        throw new Exception("Could not seek to byte offset %d", $byte_offset);
    }

    while ($bytes_remaining > 0)
    {
        $chunksize_requested = min($buffer_size, $bytes_remaining);
        $buffer = fread($handle, $chunksize_requested);
        $chunksize_real = strlen($buffer);
        if ($chunksize_real == 0)
        {
            break;
        }
        $bytes_remaining -= $chunksize_real;
        echo $buffer;
        flush();
    }
}