<?php
session_start();

// shall we log all uploaders IP addresses?
define('LOG_UPLOADER', true);

//how many resizes may one image have? set it to -1 for infinite resizes
define('MAX_RESIZED_IMAGES',4);

//when the user requests a resize. Can the resized image be bigger than the original?
define('ALLOW_BLOATING', false);


//don't change stuff beyond this point
define('DOMAINPATH',(isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','On');

include_once(ROOT.DS.'inc'.DS.'core.php');
$url = $_GET['url'];
removeMagicQuotes();
$GLOBALS['params'] = explode('/', $_GET['url']);
callHook();
