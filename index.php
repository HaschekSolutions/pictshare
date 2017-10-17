<?php

/*
 * Bootstrap the application
 * -------------------------
 *
 */

/**
 * @var \App\Application $app
 */
$app = require __DIR__.'/app/bootstrap.php';

$app->sessionStart();


define('ROOT', dirname(__FILE__));
$path = ((dirname($_SERVER['PHP_SELF']) == '/' ||
          dirname($_SERVER['PHP_SELF']) == '\\' ||
          dirname($_SERVER['PHP_SELF']) == '/index.php' ||
          dirname($_SERVER['PHP_SELF']) == '/backend.php') ? '/' : dirname($_SERVER['PHP_SELF']) . '/');
define('PATH', $path);

if ($forceDomain = $config->get('app.force_domain')) {
    define('DOMAINPATH', $forceDomain);
} else {
    define('DOMAINPATH', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') .
                         '://' . $_SERVER['HTTP_HOST']);
}


$indexController = new \App\Controllers\IndexController(new \App\Models\PictshareModel(), new \App\Support\View());
$indexController->processUrl($_GET['url']);
