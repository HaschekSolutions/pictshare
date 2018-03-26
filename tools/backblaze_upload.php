<?php 

/*
* Backblaze uploader
* This tool uploads all local images to backblaze if they don't exist yet
* You can use this to backup your images to backblaze or set it up as your data source for scaling
*
*/ 

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');
include_once(ROOT.DS.'classes/backblaze.php');

$pm = new PictshareModel();

if(in_array('sim',$argv))
{
    echo "[!!!!] SIMULATION MODE. Nothing will be uploaded [!!!!] \n\n";
    $sim = true;
}
else $sim = false;

if(in_array('recentlyrendered',$argv))
{
    echo "[O] Will only upload if the image was recently viewed. Recently meaning now minus one year \n\n";
    $recentonly = true;
}
else $recentonly = false;
    

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
    $type = pathinfo($img, PATHINFO_EXTENSION);
    $type = $pm->isTypeAllowed($type);
    if($type)
    {
        if($recentonly===true)
        {
            $recent = @file_get_contents($dir.$filename.DS.'last_rendered.txt');
            if(!$recent || (time()-$recent) > 3600*24*365) continue;
        }
        $localfiles[] = $filename;
    }
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
        echo " done.\tUploaded so far: ".renderSize($uploadsize)."\n";
    }
}
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