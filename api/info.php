<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).'/..');

header('Content-Type: application/json; charset=utf-8');

//loading default settings if exist
if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

//loading core and controllers
include_once(ROOT . DS . 'inc' .         DS. 'core.php');
//load external things if existing
if(file_exists(ROOT.'/lib/vendor/autoload.php'))
	require ROOT.'/lib/vendor/autoload.php';

if($_REQUEST['ip']=='pls') exit(getUserIP());

loadAllContentControllers();

$hash = $_REQUEST['hash'];

if(!isExistingHash($hash))
{
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
    if(!file_exists($file))
        return array('status'=>'err','reason'=>'File not found');
    $size = filesize($file);
    $size_hr = renderSize($size);
    $content_type = exec("file -bi " . escapeshellarg($file));
    if($content_type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
    {
        $type = $content_type;
        $c = explode(';',$type);
        $type = $c[0];
    }
        
    return array('hash'=>$hash,'size_bytes'=>$size,'size_interpreted'=>$size_hr,'type'=>$type,'type_interpreted'=>getTypeOfFile($file));
}