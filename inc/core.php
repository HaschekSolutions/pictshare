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
            	$path = $pm->resizeMP4($data,$cachepath);
            }
            header('Content-type: video/mp4');
			//header('Content-type: video/mpeg');
			header('Content-disposition: inline');
			header("Content-Transfer-Encoding:­ binary");
			header("Content-Length: ".filesize($path));
			
            readfile($path);
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