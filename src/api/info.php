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
    $file = getDataDir().DS.$hash.DS.$hash;
    if(!file_exists($file))
        return array('status'=>'err','reason'=>'File not found');
    $size = filesize($file);
    $size_hr = renderSize($size);
    
    $content_type = false;
    try {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME);
            $content_type = $finfo->file($file);
        }
    } catch (\Throwable $t) {
        // ignore
    }

    if (!$content_type && function_exists('mime_content_type')) {
        $content_type = @mime_content_type($file);
    }

    if (!$content_type && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $content_type = @exec("file -bi " . escapeshellarg($file));
    }

    $type = $content_type;
    if($content_type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
    {
        $c = explode(';',$type);
        $type = $c[0];
    }
        
    return array('hash'=>$hash,'size_bytes'=>$size,'size_interpreted'=>$size_hr,'type'=>$type,'type_interpreted'=>getTypeOfFile($file));
}