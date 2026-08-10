<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');

ini_set('error_log', ROOT.DS.'logs'.DS.'error.log');

//loading default settings if exist
if(!file_exists(ROOT.DS.'src'.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /src/inc/example.config.inc.php to /src/inc/config.inc.php first!');
require_once(ROOT.DS.'src'.DS.'inc'.DS.'config.inc.php');

//load external things if existing (must come before controllers are required -
//some controllers implement vendor interfaces at class-declaration time)
if(file_exists(ROOT.'/src/lib/vendor/autoload.php'))
	require_once(ROOT.'/src/lib/vendor/autoload.php');

//loading core and controllers
require_once(ROOT.DS.'src'.DS.'inc'.DS.'core.php');
require_once(ROOT.DS.'src'.DS.'inc'.DS.'api.class.php');
loadAllContentControllers();

// redis
connectRedis();

if(defined('CORS_ALLOW_ORIGIN') && CORS_ALLOW_ORIGIN !== '')
	header('Access-Control-Allow-Origin: '.CORS_ALLOW_ORIGIN);

//parse the URL to an array and filter it
$url = array_filter(explode('/',ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '','/')));

if($url[0] == 'mcp')
{
	require_once(ROOT.DS.'src'.DS.'inc'.DS.'mcp.class.php');
	PictShareMcp::serve();
	exit();
}

if($url[0] == 'api')
{
	array_shift($url); //remove "api" form the URL
    $a = new API($url);
	$return = $a->act();
	header('Content-Type: application/json');
    echo json_encode($return, JSON_PRETTY_PRINT).PHP_EOL;
	exit();
}

//not an API call, so let the architect do its magic
$routercall = architect($url);

echo $routercall.PHP_EOL;