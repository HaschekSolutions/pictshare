<?php

/**
 * MP4 re-encoder.
 *
 * Since we don't know where the mp4's come from we'll have to handle them ourselves
 * while desktop browsers are more forgiving older phones might not be
 *
 * usage: php re-encode_mp4.php [noogg] [nowebm] [noskip]
 *
 * Params:
 * noskip => Won't skip existing videos (re-renders them)
 */
if (PHP_SAPI !== 'cli') {
    exit('This script can only be called via CLI');
}

define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS . '..');

require_once ROOT . DS . 'inc/config.inc.php';
require_once ROOT . DS . 'inc/core.php';

$pm            = new PictshareModel();
$dir           = ROOT . DS . 'upload' . DS;
$dh            = opendir($dir);
$localFiles    = [];
$allowSkipping = true;

foreach ($argv as $arg) {
    if ($pm->isImage($arg) && $pm->isTypeAllowed(pathinfo($dir . $arg, PATHINFO_EXTENSION)) === 'mp4') {
        $localFiles[] = $arg;
    }
}

if (\in_array('noskip', $argv, true) || \in_array('force', $argv, true)) {
    echo "Won't skip existing files\n\n";
    $allowSkipping = false;
}

// Making sure ffmpeg is executable.
system('chmod +x ' . ROOT . DS . 'bin' . DS . 'ffmpeg');

if (\count($localFiles) === 0) {
    echo "[i] Finding local mp4 files\n";

    while (false !== ($filename = readdir($dh))) {
        $img = $dir . $filename . DS . $filename;

        if (!file_exists($img)) {
            continue;
        }

        $type = pathinfo($img, PATHINFO_EXTENSION);
        $type = $pm->isTypeAllowed($type);
        if ($type === 'mp4') {
            $localFiles[] = $filename;
        }
    }
}

if (\count($localFiles) === 0) {
    exit('No MP4 files found' . "\n");
}

echo '[i] Got ' . count($localFiles) . " files\n";
echo "[i] Starting to convert\n";

foreach ($localfiles as $hash) {
    $img = $dir . $hash . DS . $hash;
    $tmp = ROOT . DS . 'tmp' . DS . $hash;

    if ($allowSkipping && file_exists($tmp)) {
        echo "Skipping $hash\n";

        continue;
    }

    $cmd = ROOT . DS . 'bin' . DS . "ffmpeg -loglevel panic -y -i $img -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $img";
    echo "  [i] Converting $hash";
    system($cmd);
    echo "\tdone\n";
}
