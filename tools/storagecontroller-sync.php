<?php

/*
* Storage controller sync
* This tool copies syncs local files to storage controllers
*
*/ 

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set('memory_limit', -1);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

$dir = ROOT.DS.'data'.DS;
$sc = getStorageControllers();
$count = 0;
$controllers = array();
$filehashes = [];
foreach($sc as $contr)
{
    if((new $contr())->isEnabled()===true)
    {
        $controllers[] = new $contr();
        echo "[i] Found storage controller ".get_class($controllers[(count($controllers)-1)])."\n";
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

if(!in_array('p2',$argv))
{
    echo "[i] PHASE 1\n";
    echo "  [P1] Files from Storage controllers will be downloaded if they don't exist localy\n";
    sleep(1);

    foreach($controllers as $contr)
    {
        echo "  [P1] Collecting list of items from ".get_class($contr)."..\n";
        $controllerfiles = $contr->getItems(true);
        echo "\n  done. Got ".count($controllerfiles)." files\n";
        if($controllerfiles)
            foreach($controllerfiles as $cfile)
            {
                if(endswith($cfile,'.enc'))
                    $hash = substr($cfile,0,-4);
                else $hash = $cfile;

                $filehashes[$contr][] = $hash;

                if(!is_dir($dir.$hash) || !file_exists($dir.$hash.DS.$hash)) //file only on storage controller. Will download
                {
                    echo "    [P1] $hash is not on the Server but on ".get_class($contr)."\n";
                    if($enc && endswith($cfile,'.enc')) // if its encrypted and we can decrypt, then do it
                    {
                        if($sim!==true)
                        {
                            $contr->pullFile($cfile,ROOT.DS.'tmp'.DS.$cfile);
                            $enc->decryptFile(ROOT.DS.'tmp'.DS.$cfile, ROOT.DS.'tmp'.DS.$hash,base64_decode(ENCRYPTION_KEY));
                            storeFile(ROOT.DS.'tmp'.DS.$hash,$hash,true);
                            unlink(ROOT.DS.'tmp'.DS.$cfile);
                        }
                    }
                    else{ //otherwise just get the file
                        if($sim!==true){
                            $contr->pullFile($hash,ROOT.DS.'tmp'.DS.$hash);
                            storeFile(ROOT.DS.'tmp'.DS.$hash,$hash,true);
                        }
                    }
                    
                }
            }
    }


    echo "\n ----------- END OF PHASE 1 -----------\n\n";
}
else echo "[i] Skipping Phase 1\n";

echo "[i] PHASE 2\n";
echo "  [P2] Local files are synced to all storage controllers\n";
sleep(2);

echo "  [P2] Looping through local files\n";


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
    $thissize = filesize($img);
    if(!isExistingHash($hash))
        continue;
    $allhashes++;
    $allsize+=$thissize;
    $realhash = ($enc===false?$hash:$hash.'.enc');
    
    foreach($controllers as $contr)
    {

        if( (count($filehashes[$contr]) > 0 && !in_array($hash,$filehashes[$contr])) || !$contr->hashExists($realhash) )
        {
            //if($sim!==true)
            
            if(defined('ENCRYPTION_KEY') && ENCRYPTION_KEY && !$contr->hashExists($hash.'.enc')) //ok so we got an encryption key which means we'll upload the encrypted file
            {
                echo "  [P2] Controller '".get_class($contr)."' doesn't have $hash. Encrypting and uploading.. ";
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
                echo "  [P2] Controller '".get_class($contr)."' doesn't have $hash. Uploading unencrypted.. ";
                if($sim!==true)
                    $contr->pushFile($img,$hash);
                $uploadsize+=$thissize;
            }

            echo "done\n";
            $uploaded++;
            
        }
    }
        
}
closedir($dh);

echo "\n[i] Done\n";
echo "\n----------- STATS ----------\n\n";
echo "   All files found:\t$allhashes\t".renderSize($allsize)."\n";
echo "   Copied files:\t$copied\t".renderSize($copysize)."\n";
echo "   Skipped files:\t$skips\t".renderSize($skipsize)."\n";
echo "   Erroneous files:\t$errors\t".renderSize($errorsize)."\n";
echo "\n";

