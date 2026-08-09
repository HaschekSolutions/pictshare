<?php

if(php_sapi_name() !== 'cli') exit('This script can only be called via CLI');

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set('memory_limit', -1);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..');
include_once(ROOT.DS.'src'.DS.'inc/config.inc.php');
include_once(ROOT.DS.'src'.DS.'inc/core.php');

if(!defined('REDIS_CACHING') || REDIS_CACHING == true)
{
    $GLOBALS['redis'] = new Redis();
    $GLOBALS['redis']->connect((!defined('REDIS_SERVER'))?'localhost':REDIS_SERVER, (!defined('REDIS_PORT'))?6379:REDIS_PORT);
}

switch($argv[1])
{
    case 'uploadqueue':
        uploadqueue();
    break;
    case 'flushviews':
        $result = flushViews();
        echo "[i] Flushed " . count($result['flushed']) . " hash(es), skipped " . count($result['skipped']) . "\n";
    break;
    default:
        exit("[ERR] Command not found. Available commands are: uploadqueue, flushviews");
}

function uploadqueue()
{
    $queuefile = ROOT.DS.'tmp'.DS.'controllerqueue.txt';
        if(!file_exists($queuefile))
        exit("[i] File does not exist (nothing to upload)\n");
        $queue = file($queuefile);
        if(count($queue)<1)
            exit("[i] Nothing to upload\n");
        $newqueue = array();
        foreach($queue as $hash)
        {
            $hash = trim($hash);
            echo "  [i] Checking $hash\n";
            $hash = trim($hash);
            if(isExistingHash($hash)) //check if hash is still on server
            {
                echo "    [$hash] still exists locally. Uploading.. ";
                $success = storageControllerUpload($hash); // and retry the upload
                echo ($success===true?' => SUCCESS. Removing from queue':'FAILED. Will be re-added to queue')."\n";
                if(!$success)
                    $newqueue[]=$hash;
            }
        }

        file_put_contents($queuefile,implode("\n",$newqueue));
}