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
$controllers = loadAllContentControllers();
if(!in_array('TextController',$controllers))
    exit(json_encode(array('status'=>'err','reason'=>'Text controller not enabled')));

// check if client has permission to upload
executeUploadPermission();

// check write permissions first
if(!isFolderWritable(ROOT.DS.'data'))
    exit(json_encode(array('status'=>'err','reason'=>'Data directory not writable')));
else if(!isFolderWritable(ROOT.DS.'tmp'))
    exit(json_encode(array('status'=>'err','reason'=>'Temp directory not writable')));

// check for POSTed text
if($_REQUEST['api_paste_code'])
{
    $hash = getNewHash('txt',$length=10);
    $tmpfile = ROOT.DS.'tmp'.DS.$hash;
    file_put_contents($tmpfile,$_REQUEST['api_paste_code']);

    //check if this exact paste already exists
    $sha1 = sha1_file($tmpfile);
    $sha_hash = sha1Exists($sha1);
    if($sha_hash)
        exit(URL.$sha_hash);

    $answer = (new TextController())->handleUpload($tmpfile,$hash);
    if($answer['hash'] && $answer['status']=='ok')
        addSha1($answer['hash'],$sha1);

    echo URL.$hash;
}