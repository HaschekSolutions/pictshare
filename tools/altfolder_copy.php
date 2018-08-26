<?php

/*
* Alternative folder upload
* This tool copies all raw images/videos/gifs to the defined ALT_FOLDER location
* This will create a copy in the location. The location can be a mounted external server like CIFS or sshfs
* This will allow you to store a backup of your images on some other server
*
*/ 

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

if(!defined('ALT_FOLDER') || !ALT_FOLDER)
die("[X] Error: You should define the ALT_FOLDER config in your inc/config.inc.php first");

$pm = new PictshareModel();

if(in_array('sim',$argv))
{
    echo "[!!!!] SIMULATION MODE. Nothing will be uploaded [!!!!] \n\n";
    $sim = true;
}
else $sim = false;


//gather local data
echo "[i] Looping through local files\n";

$dir = ROOT.DS.'upload'.DS;
$dh  = opendir($dir);
$localfiles = array();

$allhashes=0;$allsize=0;
$skips=0;$skipsize=0;
$copied=0;$copysize=0;
$errors=0;$errorsize=0;

while (false !== ($hash = readdir($dh))) {
    if($hash=='.'||$hash=='..') continue;
    $img = $dir.$hash.DS.$hash;
    if(!file_exists($img)) continue;
    $info = strtolower(pathinfo($img, PATHINFO_EXTENSION));
    $thissize = filesize($img);
    $type = $pm->isTypeAllowed($info);
    ++$allhashes;
    $allsize+=$thissize;
    if($type)
    {
        if(file_exists(ALT_FOLDER.DS.$hash))
        {
            echo "\n  [!] Skipping existing $hash\n";
            ++$skips;
            $skipsize+=$thissize;
        }
        else
        {
            ++$copied;
            $copysize+=$thissize;
            echo "[i] Copying $hash   to   ".ALT_FOLDER.DS.$hash."                     \r";
            if($sim===false)
                copy($img,ALT_FOLDER.DS.$hash);
        }
    }
    else
    {
        ++$errors;
        $errorsize+=$thissize;
        echo "\n  [X] ERROR $hash not allowed format: $info\n";
    }
        
}

echo "\n[i] Done\n";
echo "\n----------- STATS ----------\n\n";
echo "   All files found:\t$allhashes\t".renderSize($allsize)."\n";
echo "   Copied files:\t$copied\t".renderSize($copysize)."\n";
echo "   Skipped files:\t$skips\t".renderSize($skipsize)."\n";
echo "   Erroneous files:\t$errors\t".renderSize($errorsize)."\n";
echo "\n";


function renderSize($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 