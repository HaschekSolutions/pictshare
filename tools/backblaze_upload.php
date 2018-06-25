<?php

use PictShare\Classes\Autoloader;
use PictShare\Classes\FileSizeFormatter;
use PictShare\Classes\StorageProviderFactory;
use PictShare\Models\BaseModel;

/**
 * Backblaze uploader.
 *
 * This tool uploads all local images to backblaze if they don't exist yet.
 * You can use this to backup your images to backblaze or set it up as your data source for scaling.
 */
if (PHP_SAPI !== 'cli') {
    exit('This script can only be called via CLI');
}

require_once '../Classes/Autoloader.php';
require_once '../inc/config.inc.php';

Autoloader::init();

$model      = new BaseModel();
$sim        = false;
$recentOnly = false;
$uploadSize = 0;
$dir        = UPLOAD_DIR;
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

$localStorageProvider = StorageProviderFactory::getStorageProvider(StorageProviderFactory::LOCAL_PROVIDER);

/** @var \PictShare\Classes\StorageProviders\BackblazeStorageProvider $b */
$b = StorageProviderFactory::getStorageProvider(StorageProviderFactory::BACKBLAZE_PROVIDER);
echo '[i] Loading file list from Backblaze ..';
$remoteFiles = $b->getAllFilesInBucket();
echo ' done. Got ' . \count($remoteFiles) . " files\n";

echo '[i] Loading local files ..';
while (false !== ($filename = readdir($dh))) {
    $img = $dir . $filename . '/' . $filename;

    if (!file_exists($img)) {
        continue;
    }

    $type = pathinfo($img, PATHINFO_EXTENSION);
    $type = $model->isTypeAllowed($type);

    if ($type) {
        if ($recentOnly === true) {
            $recent = @file_get_contents($dir . $filename . '/last_rendered.txt');
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
            $localFileContent = $localStorageProvider->get($hash, $hash);
            $b->save($hash, $hash, $localFileContent);
        }

        $uploadSize += filesize($dir . $hash . '/' . $hash);
        echo " done.\tUploaded so far: " . FileSizeFormatter::format($uploadSize) . "\n";
    }
}
