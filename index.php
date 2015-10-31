<?php
session_start();

if(!file_exists(ROOT.DS.'inc'.DS.'config.inc.php'))
	exit('Rename /inc/example.config.inc.php to /inc/config.inc.php first!');
include_once(ROOT.DS.'inc'.DS.'config.inc.php');

if(FORCE_DOMAIN)
	define('DOMAINPATH',FORCE_DOMAIN);
else
	define('DOMAINPATH',(isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__));
error_reporting(E_ALL & ~E_NOTICE);
if(SHOW_ERRORS)
	ini_set('display_errors','On');
else ini_set('display_errors','Off');

include_once(ROOT.DS.'inc'.DS.'core.php');
$url = $_GET['url'];
removeMagicQuotes();
$GLOBALS['params'] = explode('/', $_GET['url']);
callHook();
