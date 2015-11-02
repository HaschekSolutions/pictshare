<?php	
session_start();
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

if(FORCE_DOMAIN)
	define('DOMAINPATH',FORCE_DOMAIN);
else
	define('DOMAINPATH',(isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/');
error_reporting(E_ALL & ~E_NOTICE);
if(SHOW_ERRORS)
	ini_set('display_errors','On');
else ini_set('display_errors','Off');

include_once(ROOT.DS.'inc'.DS.'core.php');

$pm = new PictshareModel();

if(UPLOAD_CODE!=false && !$pm->uploadCodeExists($_REQUEST['upload_code']))
	exit(json_encode(array('status'=>'ERR','reason'=>'Wrong upload code provided')));

if($_REQUEST['getimage'])
{
	$url = $_REQUEST['getimage'];

	echo json_encode($pm->uploadImageFromURL($url));
}
else if($_REQUEST['base64'])
{
     $data = $_REQUEST['base64'];
     $format = $_REQUEST['format'];
     echo json_encode($pm->uploadImageFromBase64($data,$format));
}
else if($_REQUEST['getsize'])
	echo json_encode($pm->getSizeOfURL($_REQUEST['getsize']));

else
	echo json_encode(array('status'=>'ERR'));
