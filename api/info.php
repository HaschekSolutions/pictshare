<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).'/..');

//loading default settings if exist
if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

//loading core and controllers
include_once(ROOT . DS . 'inc' .         DS. 'core.php');
require_once(ROOT . DS . 'content-controllers' . DS. 'video'. DS . 'video.controller.php');

$hash = $_REQUEST['hash'];

if(!isExistingHash($hash))
{
    //check storage controllers

    exit(json_encode(array('status'=>'err','reason'=>'File not found')));
}
else
{
    $answer = getInfoAboutHash($hash);
    $answer['status'] = 'ok';
    exit(json_encode($answer));
}


function getInfoAboutHash($hash)
{
    $file = ROOT.DS.'data'.DS.$hash.DS.$hash;
    $size = filesize($file);
    $size_hr = renderSize($size);
    $content_type = exec("file -bi " . escapeshellarg($file));
    if($content_type && $content_type!=$type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
    {
        $type = $content_type;
        $c = explode(';',$type);
        $type = $c[0];
    }
        
    return array('hash'=>$hash,'size_bytes'=>$size,'size_interpreted'=>$size_hr,'type'=>$type,'type_interpreted'=>getTypeOfFile($file));
}