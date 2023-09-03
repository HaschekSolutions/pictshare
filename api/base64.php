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
include_once(ROOT . DS . 'inc' . DS. 'core.php');
//load external things if existing
if(file_exists(ROOT.'/lib/vendor/autoload.php'))
	require ROOT.'/lib/vendor/autoload.php';
loadAllContentControllers();

// check if client has permission to upload
executeUploadPermission();

// check write permissions first
if(!isFolderWritable(ROOT.DS.'data'))
    exit(json_encode(array('status'=>'err','reason'=>'Data directory not writable')));
else if(!isFolderWritable(ROOT.DS.'tmp'))
    exit(json_encode(array('status'=>'err','reason'=>'Temp directory not writable')));

$hash = sanatizeString(trim($_REQUEST['hash']))?sanatizeString(trim($_REQUEST['hash'])):false;

// check for POSTed text
if($_REQUEST['base64'])
{
    $data = $_REQUEST['base64'];
    $format = $_REQUEST['format'];

    $tmpfile = ROOT.DS.'tmp'.DS.md5(rand(0,10000).time()).time();

    base64ToFile($data, $tmpfile);

    
    //get the file type
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
    
        echo json_encode($answer);   
}




function base64_to_type($base64_string)
{
	$data = explode(',', $base64_string);
	$data = $data[1];

	$data = str_replace(' ','+',$data);
	$data = base64_decode($data);

	$info = getimagesizefromstring($data);
	
	

	trigger_error("########## FILETYPE: ".$info['mime']);


	$f = finfo_open();
	$type = finfo_buffer($f, $data, FILEINFO_MIME_TYPE);

	return $type;
}

function base64ToFile($base64_string, $output_file)
{
	$data = explode(',', $base64_string);
	$data = $data[1];
	$data = str_replace(' ','+',$data);
    $data = base64_decode($data);
    $ifp = fopen( $output_file, 'wb' ); 
    fwrite( $ifp, $data );
    fclose( $ifp ); 
}