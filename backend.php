<?php	
session_start();
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('DOMAINPATH',(isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/');

require_once (ROOT . DS . 'inc' . DS . 'core.php');


$pm = new PictshareModel();

if($_GET['getimage'])
{
	$url = $_GET['getimage'];

	echo json_encode($pm->uploadImageFromURL($url));
}
else
	echo json_encode(array('status'=>'ERR'));
