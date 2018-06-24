<?php

use \PictShare\Classes\Autoloader;
use \PictShare\Classes\FileSizeFormatter;

/**
 * Cleanup.
 *
 * This script cleans up all uploads and only leaves the original files.
 * So if you have an image and it was converted to change sizes, these files are deleted
 * and will be re-created next time they are requested.
 *
 * usage: php cleanup.php [sim]
 *
 * Params:
 * sim => Just simulate everything, don't actually delete
 */
if (PHP_SAPI !== 'cli') {
    exit('This script can only be called via CLI');
}

require_once '../Classes/Autoloader.php';
require_once '../inc/config.inc.php';
require_once '../inc/core.php';

Autoloader::init();

$pm            = new PictshareModel();
$sim           = false;
$allowSkipping = true;
$dir           = UPLOAD_DIR;
$dh            = opendir($dir);
$localFiles    = [];
$sumSize       = 0;

if (\in_array('sim', $argv, true)) {
    echo "[!!!!] SIMULATION MODE. Nothing will be deleted [!!!!] \n\n";
    $sim = true;
}

if (\in_array('noskip', $argv, true)) {
    echo "Won't skip existing files\n\n";
    $allowSkipping = false;
}

// Making sure ffmpeg is executable.
system('chmod +x ' . BASE_DIR . 'bin/ffmpeg');

echo '[i] Finding local mp4 files ..';
while (false !== ($filename = readdir($dh))) {
    $img = $dir . $filename . '/' . $filename;

    if (!file_exists($img)) {
        continue;
    }

    $type = pathinfo($img, PATHINFO_EXTENSION);
    $type = $pm->isTypeAllowed($type);

    if ($type) {
        $localFiles[] = $filename;
    }
}

if (\count($localFiles) === 0) {
    exit('No files found' . "\n");
}

echo ' done. Got ' . \count($localFiles) . " folders\n";

echo "[i] Looking for files to clean up\n";

foreach ($localFiles as $hash) {
    $dir = UPLOAD_DIR . $hash . '/';
    $dh  = opendir($dir);

    while (false !== ($filename = readdir($dh))) {
        if ($filename === $hash || $filename === 'last_rendered.txt' || !is_file($dir . $filename)) {
            continue;
        }

        echo "[$hash] $filename";

        $sumSize += filesize($dir . $filename);

        if (!$sim) {
            unlink($dir . $filename);
        }

        echo "\t" . (file_exists($dir . $filename) ? 'NOT DELETED' : 'DELETED') . "\n";
    }
}

echo "\n[!] Finished! Deleted " . FileSizeFormatter::format($sumSize) . "\n";
