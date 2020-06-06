<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));

//loading default settings if exist
if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

//loading core and controllers
include_once(ROOT.DS.'inc'.DS.'core.php');
loadAllContentControllers();


//send the URL to the architect. It'll know what to do
$url = $_GET['url']?$_GET['url']:ltrim($_SERVER['REQUEST_URI'],'/');
architect($url);