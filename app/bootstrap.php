<?php

/*
 * Load Composer's autoload
 * ------------------------
 *
 */

require __DIR__.'/../vendor/autoload.php';


/*
 * Load environmental variables from .env
 * --------------------------------------
 *
 */

$envFile = __DIR__.'/../.env';
if (file_exists($envFile)) {
    (new Dotenv\Dotenv(dirname($envFile)))->load();
}


/*
 * Prepare IoC container
 * ---------------------
 *
 */

$container = new League\Container\Container;

// register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer
);

// add the service provider to container
$container->addServiceProvider(\App\Providers\ServiceProvider::class);


/*
 * Create the application
 * ----------------------
 *
 */

$app = new \App\Application(realpath(__DIR__.'/../'), $container);

return $app;
