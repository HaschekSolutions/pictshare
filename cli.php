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


/*
 * Handle the request
 * ------------------
 *
 */

/**
 * @var \App\Controllers\CliController $cliController
 */
$cliController = $app->getContainer()->get(\App\Controllers\CliController::class);
$cliController->processCommand($argv);
