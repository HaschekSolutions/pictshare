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
if ($_FILES['file']["error"] == UPLOAD_ERR_OK || isset($_REQUEST['base64']))
{
    if (isset($_REQUEST['base64'])) {
        $tmpfile = ROOT . DS . 'tmp' . DS . md5(rand(0, 10000) . time()) . time();
        $data = explode(',', $_REQUEST['base64']);
        $data = $data[1] ?? $data[0];
        $data = str_replace(' ', '+', $data);
        file_put_contents($tmpfile, base64_decode($data));
    } else {
        $tmpfile = $_FILES['file']["tmp_name"];
    }

    //get the file type
    $type = getTypeOfFile($tmpfile);
    if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'md') {
        $type = 'markdown';
    }

    //check for duplicates
    $sha1 = sha1_file($tmpfile);
    $ehash = sha1Exists($sha1);
    if($ehash && file_exists(getDataDir().DS.$ehash.DS.$ehash))
        exit(json_encode(array('status'=>'ok','hash'=>$ehash,'filetype'=>$type,'url'=>getURL().$ehash,'duplicate'=>true)));

    //cross check filetype for controllers
    foreach($allowedcontentcontrollers as $cc)
    {
        $instance = new $cc();
        if(in_array($type, $instance->getRegisteredExtensions()))
        {
            $answer = $instance->handleUpload($tmpfile,$hash);
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
