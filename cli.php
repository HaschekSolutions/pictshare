<?php

use PictShare\Classes\Autoloader;
use PictShare\Controllers\CliController;

if (PHP_SAPI !== 'cli') {
    exit('This script can only be called via CLI');
}

define('PATH', ((dirname($_SERVER['PHP_SELF']) === '/' || dirname($_SERVER['PHP_SELF']) === '\\' || dirname($_SERVER['PHP_SELF']) === '/index.php' || dirname($_SERVER['PHP_SELF']) === '/backend.php') ? '/' : dirname($_SERVER['PHP_SELF']) . '/'));

if (!file_exists('inc/config.inc.php')) {
    exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
}

require_once 'inc/config.inc.php';

if (FORCE_DOMAIN) {
    define('DOMAINPATH', FORCE_DOMAIN);
} else {
    define('DOMAINPATH', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
}
error_reporting(E_ALL & ~E_NOTICE);
if (SHOW_ERRORS) {
    ini_set('display_errors', 'On');
} else {
    ini_set('display_errors', 'Off');
}

require_once 'Classes/Autoloader.php';

Autoloader::init();

(new CliController())->setArgs($argv)->get();
