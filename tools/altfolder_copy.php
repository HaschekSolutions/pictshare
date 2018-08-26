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
while (false !== ($hash = readdir($dh))) {
    $img = $dir.$hash.DS.$hash;
    if(!file_exists($img)) continue;
    $info = pathinfo($img, PATHINFO_EXTENSION);
    $type = $pm->isTypeAllowed($info);
    if($type)
    {
        ++$allhashes;
        //$localfiles[] = $hash;
        if(file_exists(ALT_FOLDER.DS.$hash))
        {
            echo "  [!] Skipping existing $hash\n";
            ++$skips;
        }
        else
        {
            ++$copied;
            echo "[i] Copying $hash   to   ".ALT_FOLDER.DS.$hash."                     \r";
            if($sim===false)
                copy($img,ALT_FOLDER.DS.$hash);
        }
    }
    else
        echo "  [X] ERROR $hash not allowed format: $info\n";
}

echo "\n[i] Done\n";
echo "\n----------- STATS ----------\n\n";
echo "   All files found:\t$allhashes\n";
echo "   Copied files:\t$copied\n";
echo "   Skipped files:\t$skips\n";