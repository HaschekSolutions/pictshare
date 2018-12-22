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
require_once(ROOT . DS . 'controllers' . DS. 'image'. DS . 'image.controller.php');
require_once(ROOT . DS . 'controllers' . DS. 'text'. DS . 'text.controller.php');
require_once(ROOT . DS . 'controllers' . DS. 'url'. DS . 'url.controller.php');
require_once(ROOT . DS . 'controllers' . DS. 'video'. DS . 'video.controller.php');

if(!isFolderWritable(ROOT.DS.'data'))
    exit(json_encode(array('status'=>'err','reason'=>'Data directory not writable')));
else if(!isFolderWritable(ROOT.DS.'tmp'))
    exit(json_encode(array('status'=>'err','reason'=>'Temp directory not writable')));

// check for POST upload
if ($_FILES['file']["error"] == UPLOAD_ERR_OK)
{
    //check for duplicates
    $sha1 = sha1_file($_FILES['file']["tmp_name"]);
    $hash = sha1Exists($sha1);
    if($hash)
        exit(json_encode(array('status'=>'ok','hash'=>$hash,'url'=>URL.$hash)));

    //get the file type
    $type = getTypeOfFile($_FILES['file']["tmp_name"]);

    //cross check filetype for controllers
    //
    //image?
    if(in_array($type,(new ImageController)->getRegisteredExtensions()))
    {
        $answer = (new ImageController())->handleUpload($_FILES['file']['tmp_name']);
    }
    //or, a text
    else if($type=='text')
    {
        $answer = (new TextController())->handleUpload($_FILES['file']['tmp_name']);
    }
    //or, a video
    else if(in_array($type,(new VideoController)->getRegisteredExtensions()))
    {
        $answer = (new VideoController())->handleUpload($_FILES['file']['tmp_name']);
    }

    if(!$answer)
        $answer = array('status'=>'err','reason'=>'Unsupported filetype');

    if($answer['hash'])
        addSha1($answer['hash'],$sha1);

    echo json_encode($answer);
}
else
    exit(json_encode(array('status'=>'err','reason'=>'Upload error')));
