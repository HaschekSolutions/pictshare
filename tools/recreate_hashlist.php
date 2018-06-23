<?php
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS . '..');

echo "[i] Starting recreation of hashes.csv\n";

$dir = ROOT . DS . 'upload' . DS;
$dh  = opendir($dir);
$fp  = fopen($dir . 'hashes.csv', 'wb');

if (!$fp) {
    exit("[X] Can't open hashes.csv to write");
}

while (false !== ($hash = readdir($dh))) {
    $img = $dir . $hash . DS . $hash;

    if ($hash === '.' || $hash === '..' || !file_exists($img)) {
        continue;
    }

    echo "  [s] Calculating $hash\n";

    $sha1 = sha1_file($img);
    fwrite($fp, "$sha1;$hash\n");
}

fclose($fp);

echo "[i] Finished\n";
