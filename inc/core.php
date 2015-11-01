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

function sanatizeString($string)
{
    return preg_replace("/[^a-zA-Z0-9._]+/", "", $string);
}


function callHook()
{
    global $url;
    global $default;
    $urlArray = explode("/",$url);
    
    whatToDo($urlArray);
}

function whatToDo($url)
{
    $pm = new PictshareModel();
    
    foreach($url as $el)
    {
        $el = sanatizeString($el);
        $el = strtolower($el);
        if(!$el) continue;
        
        if(IMAGE_CHANGE_CODE!=false && substr($el,0,10)=='changecode')
            $changecode = substr($el,11);
        
        if(isImage($el))
            $data['hash']=$el;
        else if(isSize($el))
            $data['size'] = $el;
        else if(isRotation($el))
            $data['rotate'] = $el;
        else if(isFilter($el))
            $data['filter'][] = $el;
        else if($legacy = isLegacyThumbnail($el)) //so old uploads will still work
        {
            $data['hash'] = $legacy['hash'];
            $data['size'] = $legacy['size'];
        }
    }
    
    if(!is_array($data) || !$data['hash'])
    {
        if($_POST['submit']==$pm->translate(3))
            $o=$pm->ProcessUploads();
        else
            $o.= $pm->renderUploadForm();
        
        $vars['content'] = $o;
        $vars['slogan'] = $pm->translate(2);
        
        render($vars);
    }
    else
        renderImage($data,$changecode);
}

function isLegacyThumbnail($val)
{
    if(strpos($val,'_'))
    {
        $a = explode('_',$val);
        $size = $a[0];
        $hash = $a[1];
        if(!isSize($size) || !isImage($hash)) return false;
        
        return array('hash'=>$hash,'size'=>$size);
    }
    else return false;
}

function isFilter($var)
{
    if(strpos($var,'_'))
    {
        $a = explode('_',$var);
        $var = $a[0];
        $val = $a[1];
        if(!is_numeric($val)) return false;
    }
    
    switch($var)
    {
        case 'negative':
        case 'grayscale': 
        case 'brightness': 
        case 'edgedetect': 
        case 'smooth': 
        case 'contrast':
        case 'pixelate': return true; 
        
        default: return false;
    }
}

function isRotation($var)
{
    switch($var)
    {
        case 'upside':
        case 'left':
        case 'right': return true;
        
        default: return false;
    }
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

function renderImage($data,$changecode)
{
    $hash = $data['hash'];
    $pm = new PictshareModel();
    $base_path = ROOT.DS.'upload'.DS.$hash.DS;
    $path = $base_path.$hash;
    $type = $pm->isTypeAllowed($pm->getTypeOfFile($path));
    $cached = false;
    
    $cachename = getCacheName($data);
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
            imagepng($im);
        break;
        case 'gif': 
            header ("Content-type: image/gif");
            $im = imagecreatefromgif($path);
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
            case 'size': $image->resize($im,$val);break;
            case 'filter': $image->filter($im,$val);break;
        }
    }
}

function getCacheName($data)
{
    ksort($data);
    $name = array();
    foreach($data as $key=>$val)
    {
        if($key!='hash')
        {
            if(!is_array($val))
                $name[] = $key.'_'.$val;
            else 
                foreach($val as $valdata)
                    $name[] = $valdata;
        }
            
    }
    
    return implode('.',$name).'.'.$data['hash'];
}

function isSize($var)
{
    if(is_numeric($var)) return true;
    $a = explode('x',$var);
    if(count($a)!=2 || !is_numeric($a[0]) || !is_numeric($a[1])) return false;
    
    return true;
}

function isImage($hash)
{
    $pm = new PictshareModel();
    if(!$hash) return false;
    return $pm->hashExists($hash);
}

function render($variables=null)
{
    if(is_array($variables))
        extract($variables);
    include (ROOT . DS . 'template.php');
}