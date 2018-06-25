<?php

declare(strict_types=1);

namespace PictShare;

use PictShare\Classes\Autoloader;

require_once '../Classes/Autoloader.php';

Autoloader::init();

\define('BASE_DIR', __DIR__ . '/../');
\define('UPLOAD_DIR', BASE_DIR . 'upload/');
