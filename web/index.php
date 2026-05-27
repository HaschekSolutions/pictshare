<?php
// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');

ini_set('error_log', ROOT.DS.'logs'.DS.'error.log');

//loading default settings if exist
if(!file_exists(ROOT.DS.'src'.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /src/inc/example.config.inc.php to /src/inc/config.inc.php first!');
require_once(ROOT.DS.'src'.DS.'inc'.DS.'config.inc.php');

//loading core and controllers
require_once(ROOT.DS.'src'.DS.'inc'.DS.'core.php');
require_once(ROOT.DS.'src'.DS.'inc'.DS.'api.class.php');
loadAllContentControllers();

//load external things if existing
if(file_exists(ROOT.'/src/lib/vendor/autoload.php'))
	require_once(ROOT.'/src/lib/vendor/autoload.php');

// redis
if(!defined('REDIS_CACHING') || REDIS_CACHING == true)
{
	try {
		$GLOBALS['redis'] = new Redis();
		$redis_host = (!defined('REDIS_SERVER')) ? 'localhost' : REDIS_SERVER;
		$redis_port = (!defined('REDIS_PORT')) ? 6379 : REDIS_PORT;
		// connect with 1.0 second timeout to prevent locking up PHP workers if Redis is down
		@$GLOBALS['redis']->connect($redis_host, $redis_port, 1.0);
	} catch (\Throwable $e) {
		$GLOBALS['redis'] = null;
		error_log("Failed to connect to Redis: " . $e->getMessage());
	}
}


//parse the URL to an array and filter it, keeping '0' values
$url = array_filter(explode('/',ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '','/')), 'strlen');

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