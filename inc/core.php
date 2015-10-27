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
    global $default;
    $urlArray = explode("/",$url);
    
    $pm = new PictshareModel();
    
    $u1 = preg_replace("/[^a-zA-Z0-9._]+/", "", $urlArray[0]);
    $u2 = preg_replace("/[^a-zA-Z0-9._]+/", "", $urlArray[1]);
    
    if(isImage($u1)) // render the image
        renderImage($u1);
    else if($u1=='store' || $u1=='originals') //render also with legacy urls
        renderImage($u2);
    else if(isResizedImage($u1,$u2)) // resize and render
        renderResizedImage($u1,$u2);
    else if($u1=='thumbs')
        renderLegacyResized($u2);
    else if(!$u1)
    {
        if($_POST['submit']==$pm->translate(3))
            $o=$pm->ProcessUploads();
        else
            $o.= $pm->renderUploadForm();
    }
    else
        header("HTTP/1.0 404 Not Found");
        
    
    $vars['content'] = $o;
    $vars['slogan'] = $pm->translate(2);
    
    render($vars);
}

function renderLegacyResized($path)
{
    
    $a = explode('_',$path);
    if(count($a)!=2) return false;
    $pm = new PictshareModel();
    
    $hash = $a[1];
    $size = $a[0];
    
    if(!$pm->hashExists($hash)) return false;
    
    
    
    renderResizedImage($size,$hash);
}

function renderImage($hash,$file=false)
{
    
    $pm = new PictshareModel();
    if(!$file) $file = DS.$hash;
    $path = ROOT.DS.'upload'.DS.$hash.$file;
    $type = $pm->isTypeAllowed($pm->getTypeOfFile($path));
    
    if($pm->countResizedImages($hash)>MAX_RESIZED_IMAGES) //if the number of max resized images is reached, just show the real one
        $path = ROOT.DS.'upload'.DS.$hash.DS.$hash;
    
    switch($type)
    {
        case 'jpg': 
            header ("Content-type: image/jpeg");
            $im = imagecreatefromjpeg($path);
            imagejpeg($im);
        break;
        case 'png': 
            header ("Content-type: image/png");
            $im = imagecreatefrompng($path);
            imagepng($im);
        break;
        case 'gif': 
            header ("Content-type: image/gif");
            $im = imagecreatefromgif($path);
            imagegif($im);
        break;
    }
    
    exit();
}

function renderResizedImage($size,$hash)
{
    $pm = new PictshareModel();
    $im = new Image();
    
    if(is_numeric($size))
        $path = $im->getImage($hash,$size);
    else 
        $path = $im->getImage($hash,explode('x',$size));
    
    renderImage($hash,$path);
}

function isImage($hash)
{
    $pm = new PictshareModel();
    if(!$hash) return false;
    return $pm->hashExists($hash);
}

function isResizedImage($resize,$hash)
{
    if(!isImage($hash) || !$resize || !$hash) return false;
    $a = explode('x',$resize);
    if(!is_numeric($resize) && count($a)!=2) return false;
    
    return true;
}

function render($variables=null)
{
    if(is_array($variables))
        extract($variables);
    include (ROOT . DS . 'template.php');
}