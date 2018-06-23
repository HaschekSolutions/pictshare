<?php

use PictShare\Classes\StorageProviderFactory;

/**
 * Backblaze uploader.
 *
 * This tool uploads all local images to backblaze if they don't exist yet.
 * You can use this to backup your images to backblaze or set it up as your data source for scaling.
 */
if (PHP_SAPI !== 'cli') {
    exit('This script can only be called via CLI');
}

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS . '..');

require_once ROOT . DS . 'inc/config.inc.php';
require_once ROOT . DS . 'inc/core.php';

$pm         = new PictshareModel();
$sim        = false;
$recentOnly = false;
$uploadSize = 0;
$dir        = ROOT . DS . 'upload' . DS;
$dh         = opendir($dir);
$localFiles = [];

if (\in_array('sim', $argv, true)) {
    echo "[!!!!] SIMULATION MODE. Nothing will be uploaded [!!!!] \n\n";
    $sim = true;
}

if (\in_array('recentlyrendered', $argv, true)) {
    echo "[O] Will only upload if the image was recently viewed. Recently meaning now minus one year \n\n";
    $recentOnly = true;
}

/** @var \PictShare\Classes\StorageProviders\BackblazeStorageProvider $b */
$b = StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER);
echo '[i] Loading file list from Backblaze ..';
$remoteFiles = $b->getAllFilesInBucket();
echo ' done. Got ' . \count($remoteFiles) . " files\n";

echo '[i] Loading local files ..';
while (false !== ($filename = readdir($dh))) {
    $img = $dir . $filename . DS . $filename;

    if (!file_exists($img)) {
        continue;
    }

    $type = pathinfo($img, PATHINFO_EXTENSION);
    $type = $pm->isTypeAllowed($type);

    if ($type) {
        if ($recentOnly === true) {
            $recent = @file_get_contents($dir . $filename . DS . 'last_rendered.txt');
            $lastRendered = \DateTime::createFromFormat('U', $recent);
            $oneYearAgo = (new \DateTime())->modify('-1 year');

            if ($lastRendered > $oneYearAgo) {
                continue;
            }
        }
        $localFiles[] = $filename;
    }
}

echo ' done. Got ' . \count($localFiles) . " files\n";

echo "[i] Checking if there are local files that are not remote\n";

foreach ($localFiles as $hash) {
    if (!$remoteFiles[$hash]) {
        echo "  [!] $hash not found on BB. Uploading...";

        if ($sim !== true) {
            $b->save($hash);
        }

        $uploadSize += filesize($dir . $hash . DS . $hash);
        echo " done.\tUploaded so far: " . renderSize($uploadSize) . "\n";
    }
}
