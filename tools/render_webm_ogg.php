<?php 

/*
* MP4 to webm and ogg converter
* When MP4s are uploaded we only have MP4s. This script converts also to
* webm and ogg for wider range of supported devices
*
* usage: php render_webm_ogg.php [noogg] [nowebm] [noskip]
*
* Params:
* noogg => Won't render videos as OGG
* nowebm => Won't render videos as webm
* noskip => Won't skip existing videos (re-renders them)
*/ 

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'inc/config.inc.php');
include_once(ROOT.DS.'inc/core.php');

$pm = new PictshareModel();

$dir = getDataDir().DS;
$dh  = opendir($dir);
$localfiles = array();

if(in_array('noskip',$argv))
{
    echo "Won't skip existing files\n\n";
    $allowskipping = false;
}
else
    $allowskipping = true;

echo "[i] Finding local mp4 files ..";
while (false !== ($filename = readdir($dh))) {
    $img = $dir.$filename.DS.$filename;
    if(!file_exists($img)) continue;
    $type = strtolower(pathinfo($img, PATHINFO_EXTENSION));
    $type = $pm->isTypeAllowed($type);
    if($type=='mp4')
        $localfiles[] = $filename;
}

if(count($localfiles)==0) exit(' No MP4 files found'."\n");

echo " done. Got ".count($localfiles)." files\n";

echo "[i] Starting to convert\n";
foreach($localfiles as $hash)
{
    $img = $dir.$hash.DS.$hash;

    if(!in_array('noogg',$argv))
    {
        $tmp = ROOT.DS.'tmp'.DS.$hash.'.ogg';
        $ogg = $dir.$hash.DS.'ogg_1.'.$hash;
        if(file_exists($ogg) && $allowskipping==true)
            echo "Skipping OGG of $hash\n";
        else
        {
            echo "  [OGG] User wants OGG. Will do.. ";
            $cmd = FFMPEG_BINARY." -y -i $img -loglevel panic -vcodec libtheora -an $tmp && cp $tmp $ogg";
            system($cmd);
            echo "done\n";
        }
    }

    if(!in_array('nowebm',$argv))
    {
        $tmp = ROOT.DS.'tmp'.DS.$hash.'.webm';
        $webm = $dir.$hash.DS.'webm_1.'.$hash;
        if(file_exists($webm) && $allowskipping==true)
            echo "Skipping WEBM of $hash\n";
        else
        {
            echo "  [WEBM] User wants WEBM. Will do.. ";
            $cmd = FFMPEG_BINARY." -y -i $img -loglevel panic -c:v libvpx -crf 10 -b:v 1M $tmp && cp $tmp $webm";
            system($cmd);
            echo "done\n";
        }
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