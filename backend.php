<?php	
session_start();
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('DOMAINPATH',(isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/');

require_once (ROOT . DS . 'inc' . DS . 'core.php');


$pm = new PictshareModel();

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

else
	echo json_encode(array('status'=>'ERR'));
