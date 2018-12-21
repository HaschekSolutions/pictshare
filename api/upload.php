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


// check for POST upload
if ($_FILES['file']["error"] == UPLOAD_ERR_OK)
{
    //get the file type
    $type = getTypeOfFile($_FILES['file']["tmp_name"]);
    //@todo: check for duplicates here

    //cross check filetype for controllers

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
        $answer = array('status'=>'err','reason'=>'Unknown error');

    echo json_encode($answer);
}