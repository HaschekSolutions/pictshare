<?php 

/*
* Backblaze uploader
* This tool uploads all local images to backblaze if they don't exist yet
* You can use this to backup your images to backblaze or set it up as your data source for scaling
*
* This scripts needs the "bcmath" library. install it with: apt-get install php-bcmath
*/

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');
include_once(ROOT.DS.'classes/backblaze.php');

$pm = new PictshareModel();

if($argv[1]=='sim')
{
    echo "[!!!!] SIMULATION MODE. Nothing will be uploaded [!!!!] \n\n";
    $sim = true;
}
else $sim = false;
    

$b = new Backblaze();
echo "[i] Loading file list from Backblaze ..";
$remotefiles = $b->getAllFilesInBucket();
echo " done. Got ".count($remotefiles)." files\n";

$uploadsize = 0;


//gather local data
$dir = ROOT.DS.'upload'.DS;
$dh  = opendir($dir);
$localfiles = array();

echo "[i] Loading local files ..";
while (false !== ($filename = readdir($dh))) {
    $img = $dir.$filename.DS.$filename;
    if(!file_exists($img)) continue;
    $type = $pm->getTypeOfFile($img);
    $type = $pm->isTypeAllowed($type);
    if($type)
        $localfiles[] = $filename;
}

echo " done. Got ".count($localfiles)." files\n";

echo "[i] Checking if there are local files that are not remote\n";
foreach($localfiles as $hash)
{
    if(!$remotefiles[$hash])
    {
        echo "  [!] $hash not found on BB. Uploading...";
        if($sim!==true)
        $b->upload($hash);
        $uploadsize+=filesize($dir.$hash.DS.$hash);
        echo " done.\tUploaded so far: ".renderSize($uploadsize,2,false)."\n";
        
    }
}

function renderSize($byte,$precision=2,$mibi=true)
{
    $base = (string)($mibi?1024:1000);
    $labels = array('K','M','G','T','P','E','Z','Y');
    for($i=8;$i>=1;$i--)
        if(bccomp($byte,bcpow($base, $i))>=0)
            return bcdiv($byte,bcpow($base, $i), $precision).' '.$labels[$i-1].($mibi?'iB':'B');
    return $byte.' Byte';
}