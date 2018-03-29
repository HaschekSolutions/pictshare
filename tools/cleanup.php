<?php 

/*
* Cleanup
* This script cleans up all uploads and only leaves the original files
* So if you have an image and it was converted to change sizes, these files are deleted
* And will be re-created next time they are requested
*
* usage: php cleanup.php [sim]
*
* Params:
* sim => Just simulate everything, don't actually delete
*/ 


if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

$pm = new PictshareModel();

if(in_array('sim',$argv))
{
    echo "[!!!!] SIMULATION MODE. Nothing will be deleted [!!!!] \n\n";
    $sim = true;
}
else $sim = false;

$dir = ROOT.DS.'upload'.DS;
$dh  = opendir($dir);
$localfiles = array();

if(in_array('noskip',$argv))
{
    echo "Won't skip existing files\n\n";
    $allowskipping = false;
}
else
    $allowskipping = true;

//making sure ffmpeg is executable
system("chmod +x ".ROOT.DS.'bin'.DS.'ffmpeg');

echo "[i] Finding local mp4 files ..";
while (false !== ($filename = readdir($dh))) {
    $img = $dir.$filename.DS.$filename;
    if(!file_exists($img)) continue;
    $type = pathinfo($img, PATHINFO_EXTENSION);
    $type = $pm->isTypeAllowed($type);
    if($type)
        $localfiles[] = $filename;
}

if(count($localfiles)==0) exit('No files found'."\n");

echo " done. Got ".count($localfiles)." folders\n";

$sumsize = 0;

echo "[i] Looking for files to clean up\n";
foreach($localfiles as $hash)
{
    $dir = ROOT.DS.'upload'.DS.$hash.DS;
    $dh  = opendir($dir);

    while (false !== ($filename = readdir($dh))) {
        if(!is_file($dir.$filename) || $filename==$hash || $filename == 'last_rendered.txt')
            continue;
        
        echo "[$hash] $filename";
        $sumsize+=filesize($dir.$filename);
        if(!$sim)
            unlink($dir.$filename);

        echo "\t".(file_exists($dir.$filename)?'NOT DELETED':'DELETED')."\n";
    }

}

echo "\n[!] Finished! Deleted ".renderSize($sumsize)."\n";

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
