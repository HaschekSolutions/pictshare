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


/*
 * Handle the request
 * ------------------
 *
 */

/**
 * @var \App\Controllers\IndexController $indexController
 */
$indexController = $app->getContainer()->get(\App\Controllers\IndexController::class);
$indexController->processUrl($_GET['url']);
