<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');

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
$allowedcontentcontrollers = loadAllContentControllers();

// check write permissions first
if(!isFolderWritable(getDataDir()))
    exit(json_encode(array('status'=>'err','reason'=>'Data directory not writable')));
else if(!isFolderWritable(ROOT.DS.'tmp'))
    exit(json_encode(array('status'=>'err','reason'=>'Temp directory not writable')));

// check if client has permission to upload
executeUploadPermission();

if(isset($_REQUEST['hash']))
    $hash = sanatizeString(trim($_REQUEST['hash']));
else
    $hash = false;

// check for POST upload
if ($_FILES['file']["error"] == UPLOAD_ERR_OK)
{
    //get the file type
    $type = getTypeOfFile($_FILES['file']["tmp_name"]);

    //check for duplicates
    $sha1 = sha1_file($_FILES['file']["tmp_name"]);
    $ehash = sha1Exists($sha1);
    if($ehash && file_exists(getDataDir().DS.$ehash.DS.$ehash))
        exit(json_encode(array('status'=>'ok','hash'=>$ehash,'filetype'=>$type,'url'=>getURL().$ehash)));

    //cross check filetype for controllers
    //
    //image?

    foreach($allowedcontentcontrollers as $cc)
    {
        if(in_array($type,(new $cc)->getRegisteredExtensions()))
        {
            $answer = (new $cc())->handleUpload($_FILES['file']['tmp_name'],$hash);
            break;
        }
    }


    if(!$answer)
        $answer = array('status'=>'err','reason'=>'Unsupported filetype: '.$type,'filetype'=>$type);

    if($answer['hash'] && $answer['status']=='ok')
    {
        $answer['filetype'] = $type;
        //add this sha1 to the list
        addSha1($answer['hash'],$sha1);

        if(getDeleteCodeOfHash($answer['hash']))
        {
            $answer['delete_code'] = getDeleteCodeOfHash($answer['hash']);
            $answer['delete_url'] = getURL().'delete_'.getDeleteCodeOfHash($answer['hash']).'/'.$answer['hash'];
        }
            

        storageControllerUpload($answer['hash']);
    }

    echo json_encode($answer);
}
else
    exit(json_encode(array('status'=>'err','reason'=>'Upload error')));
