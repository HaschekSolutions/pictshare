<?php

/*
* Storage controller sync
* This tool copies syncs local files to storage controllers
*
*/ 

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

$sc = getStorageControllers();
$count = 0;
$controllers = array();
foreach($sc as $contr)
{
    if((new $contr())->isEnabled()===true)
    {
        $controllers[] = new $contr();
    }
}

if(count($controllers)==0)
    die("[X] Error: You should define at least one storage controller in your inc/config.inc.php first");

if(in_array('sim',$argv))
{
    echo "[!!!!] SIMULATION MODE. Nothing will be uploaded [!!!!] \n\n";
    $sim = true;
}
else $sim = false;

$enc=false;
if(defined('ENCRYPTION_KEY') && ENCRYPTION_KEY)
{
    $enc = new Encryption;
    echo "[i] Encryption key found. Will encrypt on Storage controllers\n";
}


echo "[i] Looping through local files\n";

$dir = ROOT.DS.'data'.DS;
$dh  = opendir($dir);
$localfiles = array();

$allhashes=0;$allsize=0;
$skips=0;$skipsize=0;
$copied=0;$copysize=0;
$errors=0;$errorsize=0;
$uploaded=0;$uploadsize=0;

while (false !== ($hash = readdir($dh))) {
    if($hash=='.'||$hash=='..') continue;
    $img = $dir.$hash.DS.$hash;
    if(!file_exists($img)) continue;
    //$info = strtolower(pathinfo($img, PATHINFO_EXTENSION));
    $thissize = filesize($img);
    if(!isExistingHash($hash))
        continue;
    $allhashes++;
    $allsize+=$thissize;
    
    foreach($controllers as $contr)
    {
        if((!$enc && !$contr->hashExists($hash)) || $enc && !$contr->hashExists($hash.'.enc'))
        {
            //if($sim!==true)
            
            if(defined('ENCRYPTION_KEY') && ENCRYPTION_KEY && !$contr->hashExists($hash.'.enc')) //ok so we got an encryption key which means we'll upload the encrypted file
            {
                echo "  [u] Controller '".get_class($contr)."' doesn't have $hash. Encrypting and uploading.. ";
                $encryptedfile = $img.'.enc';
                
                if($sim!==true)
                {
                    $enc->encryptFile($img,$encryptedfile,base64_decode(ENCRYPTION_KEY));
                    $uploadsize+=filesize($encryptedfile);
                    $contr->pushFile($encryptedfile,$hash.'.enc');  
                    unlink($encryptedfile);
                }
            }
            else
            {
                echo "  [u] Controller '".get_class($contr)."' doesn't have $hash. Uploading unencrypted.. ";
                if($sim!==true)
                    $contr->pushFile($img,$hash);
                $uploadsize+=$thissize;
            }

            echo "done\n";
            $uploaded++;
            
        }
    }
        
}

echo "\n[i] Done\n";
echo "\n----------- STATS ----------\n\n";
echo "   All files found:\t$allhashes\t".renderSize($allsize)."\n";
echo "   Copied files:\t$copied\t".renderSize($copysize)."\n";
echo "   Skipped files:\t$skips\t".renderSize($skipsize)."\n";
echo "   Erroneous files:\t$errors\t".renderSize($errorsize)."\n";
echo "\n";
