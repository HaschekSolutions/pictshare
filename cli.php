<?php

/*
 * Bootstrap the application
 * -------------------------
 *
 */

require __DIR__.'app/bootstrap.php';





define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
define('CLI', true);
define('PATH', ((dirname($_SERVER['PHP_SELF']) == '/' || dirname($_SERVER['PHP_SELF']) == '\\' ||
                   dirname($_SERVER['PHP_SELF']) == '/index.php' ||
                   dirname($_SERVER['PHP_SELF']) == '/backend.php') ? '/' : dirname($_SERVER['PHP_SELF']) . '/')
);

if (!file_exists(ROOT . DS . 'inc' . DS . 'config.inc.php')) {
    exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
}
include_once(ROOT . DS . 'inc' . DS . 'config.inc.php');

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
include_once(ROOT . DS . 'inc' . DS . 'core.php');

$action = $argv[2];
$params = $argv;

//lose first param (self name)
array_shift($params);

$model = new \App\Models\PictshareModel();
$model->backend($params);
