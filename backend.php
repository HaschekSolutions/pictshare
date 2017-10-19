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

$backendController = $app->getContainer()->get(\App\Controllers\BackendController::class);
$backendController->processRequest($_REQUEST);
