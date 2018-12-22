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

// basic path definitions
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).'/..');

//loading default settings if exist
if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

//loading core and controllers
include_once(ROOT.DS.'inc'.DS.'core.php');
require_once(ROOT . DS . 'controllers' . DS. 'video'. DS . 'video.controller.php');

if(!defined('FFMPEG_BINARY')||FFMPEG_BINARY=='' || !FFMPEG_BINARY) exit('Error: FFMPEG_BINARY not defined, no clue where to look');

$vc = new VideoController();
$dir = ROOT.DS.'data'.DS;
$dh  = opendir($dir);
$localfiles = array();

foreach($argv as $arg)
{
    if(isExistingHash($arg) && in_array(pathinfo($dir.$arg, PATHINFO_EXTENSION),$vc->getRegisteredExtensions()))
        $localfiles[] = $arg;
}

if(in_array('altfolder',$argv) && defined('ALT_FOLDER') && ALT_FOLDER && is_dir(ALT_FOLDER) )
{
    echo "[i] Checking only the alt folder\n";
    $dir = ALT_FOLDER.DS;
    $dh  = opendir($dir);
    while (false !== ($filename = readdir($dh))) {
        $vid = $dir.$filename;
        $hash = $filename;
        echo "\r[$filename]               ";
        if(!file_exists($vid)) continue;
        $type = strtolower(pathinfo($vid, PATHINFO_EXTENSION));
        if(in_array($type,$vc->getRegisteredExtensions()))
        {
            echo "\n [i] $filename is ..\t";
            $valid = $vc->rightEncodedMP4($vid);
            $tmp = ROOT.DS.'tmp'.DS.$hash;
            $cmd = FFMPEG_BINARY." -loglevel panic -y -i $vid -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $img";
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
        if(in_array($type,$vc->getRegisteredExtensions()))
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
    if($vc->rightEncodedMP4($mp4))
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
    $cmd = FFMPEG_BINARY." -loglevel panic -y -i $mp4 -vcodec libx264 -an -profile:v baseline -level 3.0 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" $tmp && cp $tmp $mp4";
    echo "  [i] Converting '$hash'";
    system($cmd);
    if(defined('ALT_FOLDER') && ALT_FOLDER && is_dir(ALT_FOLDER))
        copy($mp4,ALT_FOLDER.DS.$hash);

    //file got a new hash so add that as well
    addSha1($hash,sha1_file($mp4));
    
    echo "\tdone\n";

}
