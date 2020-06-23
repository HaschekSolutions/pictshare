<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).'/..');

//loading default settings if exist
if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

//loading core and controllers
include_once(ROOT . DS . 'inc' . DS. 'core.php');
loadAllContentControllers();

// check if client has permission to upload
executeUploadPermission();

// check write permissions first
if(!isFolderWritable(ROOT.DS.'data'))
    exit(json_encode(array('status'=>'err','reason'=>'Data directory not writable')));
else if(!isFolderWritable(ROOT.DS.'tmp'))
    exit(json_encode(array('status'=>'err','reason'=>'Temp directory not writable')));

$hash = sanatizeString(trim($_REQUEST['hash']))?sanatizeString(trim($_REQUEST['hash'])):false;

$url = trim($_REQUEST['url']);

if(!$url || !startsWith($url, 'http'))
    exit(json_encode(array('status'=>'err','reason'=>'Invalid URL')));
    
//@todo: let user decide max upload size via config and set php_ini var
else if(remote_filesize($url)*0.000001 > 20)
    exit(json_encode(array('status'=>'err','reason'=>'File too big. 20MB max')));

$name = basename($url);
$tmpfile = ROOT.DS.'tmp'.DS.$name;
file_put_contents($tmpfile,file_get_contents($url));

$type = getTypeOfFile($tmpfile);

//check for duplicates
$sha1 = sha1_file($tmpfile);
$ehash = sha1Exists($sha1);
if($ehash && file_exists(ROOT.DS.'data'.DS.$ehash.DS.$ehash))
    exit(json_encode(array('status'=>'ok','hash'=>$ehash,'filetype'=>$type,'url'=>URL.$ehash)));

//cross check filetype for controllers
//
//image?
if(in_array($type,(new ImageController)->getRegisteredExtensions()))
{
    $answer = (new ImageController())->handleUpload($tmpfile,$hash);
}
//or, a text
else if($type=='text')
{
    $answer = (new TextController())->handleUpload($tmpfile,$hash);
}
//or, a video
else if(in_array($type,(new VideoController)->getRegisteredExtensions()))
{
    $answer = (new VideoController())->handleUpload($tmpfile,$hash);
}

if(!$answer)
        $answer = array('status'=>'err','reason'=>'Unsupported filetype','filetype'=>$type);

if($answer['hash'] && $answer['status']=='ok')
    {
        $answer['filetype'] = $type;
        //add this sha1 to the list
        addSha1($answer['hash'],$sha1);

        if(getDeleteCodeOfHash($answer['hash']))
        {
            $answer['delete_code'] = getDeleteCodeOfHash($answer['hash']);
            $answer['delete_url'] = URL.'delete_'.getDeleteCodeOfHash($answer['hash']).'/'.$answer['hash'];
        }
            

        storageControllerUpload($answer['hash']);
    }

    if($answer['hash'] && $answer['status']=='ok')
    {
        //add this sha1 to the list
        addSha1($answer['hash'],$sha1);

        if(getDeleteCodeOfHash($answer['hash']))
        {
            $answer['delete_code'] = getDeleteCodeOfHash($answer['hash']);
            $answer['delete_url'] = URL.'delete_'.getDeleteCodeOfHash($answer['hash']).'/'.$answer['hash'];
        }

        storageControllerUpload($answer['hash']);
    }

    echo json_encode($answer);  



function remote_filesize($url) {
    static $regex = '/^Content-Length: *+\K\d++$/im';
    if (!$fp = @fopen($url, 'rb'))
        return false;
    if (
        isset($http_response_header) &&
        preg_match($regex, implode("\n", $http_response_header), $matches)
    )
        return (int)$matches[0];
    return strlen(stream_get_contents($fp));
}