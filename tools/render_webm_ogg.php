<?php

use PictShare\Classes\Autoloader;

/**
 * MP4 to webm and ogg converter.
 *
 * When MP4s are uploaded we only have MP4s. This script converts also to
 * webm and ogg for wider range of supported devices.
 *
 * usage: php render_webm_ogg.php [noogg] [nowebm] [noskip]
 *
 * Params:
 * noogg => Won't render videos as OGG
 * nowebm => Won't render videos as webm
 * noskip => Won't skip existing videos (re-renders them)
 */
if (PHP_SAPI !== 'cli') {
    exit('This script can only be called via CLI');
}

require_once '../Classes/Autoloader.php';
require_once '../inc/config.inc.php';
require_once '../inc/core.php';

Autoloader::init();

$pm = new PictshareModel();
$dir = UPLOAD_DIR;
$dh  = opendir($dir);
$localFiles = [];
$allowSkipping = true;

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

    if ($type === 'mp4') {
        $localFiles[] = $filename;
    }
}

if (\count($localFiles) === 0) {
    exit(' No MP4 files found' . "\n");
}

echo ' done. Got ' . \count($localFiles) . " files\n";
echo "[i] Starting to convert\n";

foreach ($localFiles as $hash) {
    $img = $dir . $hash . '/' . $hash;

    if (!\in_array('noogg', $argv, true)) {
        $tmp = BASE_DIR . 'tmp/' . $hash . '.ogg';
        $ogg = $dir . $hash . '/ogg_1.' . $hash;

        if ($allowSkipping && file_exists($ogg)) {
            echo "Skipping OGG of $hash\n";
        } else {
            echo '  [OGG] User wants OGG. Will do.. ';
            $cmd = BASE_DIR . "bin/ffmpeg -y -i $img -loglevel panic -vcodec libtheora -an $tmp && cp $tmp $ogg";
            system($cmd);
            echo "done\n";
        }
    }

    if (!\in_array('nowebm', $argv, true)) {
        $tmp = BASE_DIR . 'tmp/' . $hash . '.webm';
        $webm = $dir . $hash . '/webm_1.' . $hash;

        if ($allowSkipping && file_exists($webm)) {
            echo "Skipping WEBM of $hash\n";
        } else {
            echo '  [WEBM] User wants WEBM. Will do.. ';
            $cmd = BASE_DIR . "bin/ffmpeg -y -i $img -loglevel panic -c:v libvpx -crf 10 -b:v 1M $tmp && cp $tmp $webm";
            system($cmd);
            echo "done\n";
        }
    }
}
