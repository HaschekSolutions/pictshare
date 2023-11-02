<?php 
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');

echo "[i] Starting recreation of hashes.csv\n";

$dir = getDataDir().DS;
$dh  = opendir($dir);

$fp = fopen($dir.'hashes.csv','w');
if(!$fp)
exit("[X] Can't open hashes.csv to write");

while (false !== ($hash = readdir($dh))) {
    $img = $dir.$hash.DS.$hash;
    if(!file_exists($img) || $hash=='.' || $hash=='..') continue;
    echo "  [s] Calculating $hash\n";
    $sha1 = sha1_file($img);
    fwrite($fp,"$sha1;$hash\n");
}

fclose($fp);

echo "[i] Finished\n";