<?php

use PictShare\Classes\Autoloader;

session_cache_limiter('public');
$expiry = 90; //days
session_cache_expire((new \DateTime())->modify('+' . $expiry . ' days')->getTimestamp());
session_start();
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__);
define('PATH', ((dirname($_SERVER['PHP_SELF']) === '/' || dirname($_SERVER['PHP_SELF']) === '\\' || dirname($_SERVER['PHP_SELF']) === '/index.php' || dirname($_SERVER['PHP_SELF']) === '/backend.php') ? '/' : dirname($_SERVER['PHP_SELF']) . '/'));

if (!file_exists(ROOT . DS . 'inc' . DS . 'config.inc.php')) {
    exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
}

require_once ROOT . DS . 'inc' . DS . 'config.inc.php';

if (FORCE_DOMAIN) {
    define('DOMAINPATH', FORCE_DOMAIN);
} else {
    define('DOMAINPATH', ($_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
}
error_reporting(E_ALL & ~E_NOTICE);
if (SHOW_ERRORS) {
    ini_set('display_errors', 'On');
} else {
    ini_set('display_errors', 'Off');
}

require_once ROOT . DS . 'Classes/Autoloader.php';
require_once ROOT . DS . 'inc' . DS . 'core.php';

Autoloader::init();

$url = $_GET['url'];
removeMagicQuotes();
$GLOBALS['params'] = explode('/', $_GET['url']);
callHook();
