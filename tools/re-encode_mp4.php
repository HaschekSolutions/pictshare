<?php 

/*
* MP4 re-encoder
* Since we don't know where the mp4's come from we'll have to handle them ourselves
* While desktop browsers are more forgiving older phones might not be
*
* usage: php re-encode_mp4.php [noogg] [nowebm] [noskip]
*
* Params:
* altfolder => Will check the altfolder (if exists) for falsly encoded files
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

if(in_array('altfolder',$argv) && defined('ALT_FOLDER') && ALT_FOLDER && is_dir(ALT_FOLDER) )
{
    echo "[i] Checking only the alt folder\n";
    $dir = ALT_FOLDER.DS;
    $dh  = opendir($dir);
    while (false !== ($filename = readdir($dh))) {
        $img = $dir.$filename;
        $hash = $filename;
        echo "\r[$filename]               ";
        if(!file_exists($img)) continue;
        $type = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        $type = $pm->isTypeAllowed($type);
        if($type=='mp4')
        {
            echo "\n [i] $filename is ..\t";
            $valid = checkFileForValidMP4($img);
            $tmp = ROOT.DS.'tmp'.DS.$hash;
            $cmd = ROOT.DS.'bin'.DS."ffmpeg -loglevel panic -y -i $img -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $img";
            echo ($valid?'Valid'."\n":'Not valid => Converting..');
            if(!$valid)
            {
                system($cmd);
                echo " done\n";
                unlink($tmp);
            }
        }
    }
}

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


//TESTING
echo "[i] Checking hashes for wrongly encoded ones\n";
foreach($localfiles as $akey => $hash)
{
    $mp4 = $dir.$hash.DS.$hash;
    if(checkFileForValidMP4($mp4))
    {
        echo " [i] Skipping $hash because it's already correctly encoded\n";
        unset($localfiles[$akey]);
    }
}

echo "[i] Starting to convert\n";
foreach($localfiles as $hash)
{
    $mp4 = $dir.$hash.DS.$hash;
    $tmp = ROOT.DS.'tmp'.DS.$hash;
    $cmd = ROOT.DS.'bin'.DS."ffmpeg -loglevel panic -y -i $mp4 -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $mp4";
    echo "  [i] Converting '$hash'";
    system($cmd);
    if(defined('ALT_FOLDER') && ALT_FOLDER && is_dir(ALT_FOLDER))
        copy($mp4,ALT_FOLDER.DS.$hash);
    echo "\tdone\n";

}

function checkFileForValidMP4($file)
{
    $hash = md5($file);
    $cmd = ROOT.DS.'bin'.DS."ffmpeg -i $file -hide_banner 2> ".ROOT.DS.'tmp'.DS.$hash.'.txt';
    system($cmd);
    $results = file(ROOT.DS.'tmp'.DS.$hash.'.txt');
    foreach($results as $l)
    {
        $elements = explode(':',trim($l));
        $key=trim(array_shift($elements));
        $value = trim(implode(':',$elements));
        if($key=='encoder')
        {
            if(startsWith(strtolower($value),'lav'))
            {
                return true;
            } else return false;
        }
    }
    unlink(ROOT.DS.'tmp'.DS.$hash.'.txt');
    return false;
}