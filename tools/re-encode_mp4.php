<?php 

/*
* MP4 re-encoder
* Since we don't know where the mp4's come from we'll have to handle them ourselves
* While desktop browsers are more forgiving older phones might not be
*
* usage: php re-encode_mp4.php [noogg] [nowebm] [noskip]
*
* Params:
* noskip => Won't skip existing videos (re-renders them)
*/ 


if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

$pm = new PictshareModel();

$dir = ROOT.DS.'upload'.DS;
$dh  = opendir($dir);
$localfiles = array();

foreach($argv as $arg)
{
    if($pm->isImage($arg) && $pm->isTypeAllowed(pathinfo($dir.$arg, PATHINFO_EXTENSION)) == 'mp4')
        $localfiles[] = $arg;
}

if(in_array('noskip',$argv) || in_array('force',$argv))
{
    echo "Won't skip existing files\n\n";
    $allowskipping = false;
}
else
    $allowskipping = true;

//making sure ffmpeg is executable
system("chmod +x ".ROOT.DS.'bin'.DS.'ffmpeg');

if(count($localfiles)==0)
{
    echo "[i] Finding local mp4 files\n";
    while (false !== ($filename = readdir($dh))) {
        $img = $dir.$filename.DS.$filename;
        if(!file_exists($img)) continue;
        $type = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        $type = $pm->isTypeAllowed($type);
        if($type=='mp4')
            $localfiles[] = $filename;
    }
}

if(count($localfiles)==0) exit('No MP4 files found'."\n");

echo "[i] Got ".count($localfiles)." files\n";

echo "[i] Starting to convert\n";
foreach($localfiles as $hash)
{
    $img = $dir.$hash.DS.$hash;
    $tmp = ROOT.DS.'tmp'.DS.$hash;
    if(file_exists($tmp) && $allowskipping==true)
        echo "Skipping $hash\n";
    else 
    {
        $cmd = ROOT.DS.'bin'.DS."ffmpeg -loglevel panic -y -i $img -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $img";
        echo "  [i] Converting $hash";
        system($cmd);
        echo "\tdone\n";
    }

}
