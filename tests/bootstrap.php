<?php
// tests/bootstrap.php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', realpath(__DIR__ . '/..'));

// Isolated data directory for all test runs
$testDataDir = sys_get_temp_dir() . DS . 'pictshare_test_data';
foreach ([$testDataDir, ROOT . DS . 'tmp', ROOT . DS . 'logs'] as $d) {
    if (!is_dir($d)) mkdir($d, 0777, true);
}
define('TEST_DATA_DIR', $testDataDir);

// getDataDir() in core.php checks this constant first
define('_TEST_DATA_OVERRIDE', $testDataDir);

// Required for sha1 index and naughty list
if (!file_exists($testDataDir . DS . 'sha1.csv')) touch($testDataDir . DS . 'sha1.csv');
if (!file_exists($testDataDir . DS . 'naughty.csv')) touch($testDataDir . DS . 'naughty.csv');

// Server vars PHPUnit CLI won't set
$_SERVER += [
    'HTTP_USER_AGENT' => 'PHPUnit',
    'REMOTE_ADDR'     => '127.0.0.1',
    'HTTP_HOST'       => 'localhost',
    'HTTP_ACCEPT'     => 'text/html',
];

require_once __DIR__ . '/testconfig.php';
require_once ROOT . DS . 'src' . DS . 'inc' . DS . 'core.php';

// resize.php and filters.php define functions used in unit tests
require_once ROOT . DS . 'src' . DS . 'content-controllers' . DS . 'image' . DS . 'resize.php';
require_once ROOT . DS . 'src' . DS . 'content-controllers' . DS . 'image' . DS . 'filters.php';

require_once ROOT . DS . 'src' . DS . 'inc' . DS . 'api.class.php';
loadAllContentControllers();

if (file_exists(ROOT . '/src/lib/vendor/autoload.php'))
    require_once ROOT . '/src/lib/vendor/autoload.php';

// Redis disabled — GLOBALS['redis'] stays null (REDIS_CACHING=false skips init in index.php)
