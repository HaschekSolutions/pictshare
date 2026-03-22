<?php

spl_autoload_register('autoload');

//disable output buffering
if (ob_get_level()) ob_end_clean();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if(!defined('FFMPEG_BINARY'))
    define('FFMPEG_BINARY','ffmpeg');

/**
 * The Architect function is the main controller 
 * who will decide what to do with any given URL
 * by feeding it to the other controllers
 */
function architect($u)
{

    // check if client address is allowed
    if($u[0] == '' || $u[0] == '/')
    {
        $forbidden = false;
        if(defined('ALLOWED_SUBNET') && ALLOWED_SUBNET != '' && !isIPInRange( getUserIP(), ALLOWED_SUBNET ))
        {
            $forbidden = true;
        }
        return renderTemplate('index.html.php',['main'=>renderTemplate('main.html.php',['forbidden'=>$forbidden])]);
    }

    if($u[0] == 'report')
    {
        $reports = getReports();
        $alreadyreported = [];
        $o = [];
        foreach($reports as $report)
            $alreadyreported = array_merge($alreadyreported, $report['hashes']);
        if(isset($_POST['urls']) && isset($_POST['note']))
        {
            $urls = $_POST['urls'];
            $note = $_POST['note'];

            $hashes = [];

            foreach(explode("\n", $urls) as $url)
            {
                $url = trim($url);
                if(!$url) continue;
                if(!startsWith(strtolower($url), strtolower(URL))){
                    $o[] = 'Skipped '.$url.', because it does not start with the base URL: '.URL;
                    continue; // we only accept URLs that start with the base URL
                }
                $parts = explode('/', $url);
                foreach($parts as $part)
                {
                    if(!mightBeAHash($part)) continue;
                    if(!isExistingHash($part)) continue;
                    else {
                        $hash = $part;
                        if(!in_array($hash, $alreadyreported))
                            $hashes[] = $hash;
                        else
                            $o[] = 'Skipped '.$url.', because already reported';
                        break;
                    }
                }
            }

            if(count($hashes) == 0)
            {
                return renderTemplate('index.html.php',['main'=>implode("<br/>",$o)."<div class='alert alert-danger'>No new URLs were reported</div>"]);
            }

            $id = addReport($hashes, $note);
            return renderTemplate('index.html.php',['main'=>implode("<br/>",$o)."<div class='alert alert-success'><strong>Report ID: $id</strong><br>Your report contained ".count($hashes)." verified file(s) and has been submitted.</div>"]);
        }
        else
        {
            return renderTemplate('index.html.php',['main'=>renderTemplate('report.form.html.php')]);
        }
    }

    // admin logic
    if($u[0] == 'admin' && defined('ADMIN_PASSWORD') && ADMIN_PASSWORD != '')
    {
        // block checks
        if (defined('ALLOWED_SUBNET') && ALLOWED_SUBNET != '' && !isIPInRange(getUserIP(), ALLOWED_SUBNET))
            return;
        else if (defined('MASTER_DELETE_IP') && MASTER_DELETE_IP != '' && !isIPInRange(getUserIP(), MASTER_DELETE_IP))
            return;
        session_start();
        switch($u[1]){
            case 'rebuild-meta':
                if(!$_SESSION['admin'])
                    header('Location: /admin');
                return renderTemplate('index.html.php',['main'=>'<code><pre>'.rebuildMeta().'</pre></code>']);
            case 'stats':
                if(!$_SESSION['admin']) {
                    header('Location: /admin');
                    return;
                }
                if(isset($u[2]) && $u[2] === 'data') {
                    // HTMX fragment — return bare tbody rows, no layout wrapper
                    $page   = max(1, (int)($_GET['page'] ?? 1));
                    $sort   = $_GET['sort'] ?? 'uploaded';
                    $dir    = $_GET['dir']  ?? 'desc';
                    $q      = $_GET['q']    ?? '';
                    if(isCacheStale()) rebuildStatsCache();
                    $result = getStatsPage($page, $sort, $dir, $q);
                    return renderTemplate('admin.stats-table.html.php', $result);
                }
                if(isCacheStale()) rebuildStatsCache();
                $builtAt = isset($GLOBALS['redis']) && $GLOBALS['redis']
                    ? (int)$GLOBALS['redis']->get('stats:built_at')
                    : 0;
                return renderTemplate('index.html.php',['main'=>renderTemplate('admin.stats.html.php',['built_at'=>$builtAt])]);
            case 'logs':
                if(!$_SESSION['admin'])
                    header('Location: /admin');
                switch($u[2])
                {
                    case 'app':
                        return renderTemplate('index.html.php',['main'=>renderTemplate('admin.logs-table.html.php',['type'=>'app','logs'=>getLogs('app',$u[3])])]);
                    case 'error':
                        return renderTemplate('index.html.php',['main'=>renderTemplate('admin.logs-table.html.php',['type'=>'error','logs'=>getLogs('error',$u[3])])]);
                    case 'views':
                        return renderTemplate('index.html.php',['main'=>renderTemplate('admin.logs-table.html.php',['type'=>'views','logs'=>getLogs('views',$u[3])])]);
                    default:
                        return renderTemplate('index.html.php',['main'=>renderTemplate('admin.logs.html.php')]);
                }
            case 'reports':
                if(!$_SESSION['admin']) {
                    header('Location: /admin');
                    return;
                }
                if($u[2]=='delete'){
                    $hash = $u[3];
                    deleteHash($hash);
                }
                return renderTemplate('index.html.php',['main'=>renderTemplate('admin.reports.html.php',['reports'=>getReports()])]);
            default:
                if($_REQUEST['password'] && $_REQUEST['password']== ADMIN_PASSWORD)
                {
                    $_SESSION['admin'] = true;
                }
                if($_SESSION['admin'])
                {
                    if(isset($_REQUEST['logout']))
                    {
                        unset($_SESSION['admin']);
                        session_destroy();
                    }
                }
                return renderTemplate('index.html.php',['main'=>renderTemplate('admin.html.php')]);
        }
    }

    //check cache
    if(isset($GLOBALS['redis']))
    {
        $cache_data = $GLOBALS['redis']->get('cache:byurl:'.implode('/',$u));
        if($cache_data)
        {
            list($cc, $hash) = explode(';', $cache_data);
            if(defined('LOG_VIEWS') && LOG_VIEWS===true)
                addToLog(getUserIP()."\tviewed\t$hash\tFrom cache. Agent:\t".$_SERVER['HTTP_USER_AGENT']."\tref:\t".$_SERVER['HTTP_REFERER'], ROOT.DS.'logs/views.log');
            $GLOBALS['redis']->incr("served:$hash");
            return (new $cc())->handleHash($hash,$u);
        }
    }

    //check all parts of the URL for a valid hash
    $hash = false;
    $sc = getStorageControllers();
    foreach($u as $el)
    {
        if(isExistingHash($el))
        {
            $hash = $el;
            if(defined('LOG_VIEWS') && LOG_VIEWS===true)
                addToLog(getUserIP()."\tviewed\t$hash\tIt was locally found. Agent:\t".$_SERVER['HTTP_USER_AGENT']."\tref:\t".$_SERVER['HTTP_REFERER'], ROOT.DS.'logs/views.log');
            break;
        }
        // if we don't have a hash yet but the element looks like it could be a hash
        if($hash === false && mightBeAHash($el))
        {
            foreach($sc as $contr)
            {
                $c = new $contr();

                if($c->isEnabled()===true && $c->hashExists($el))
                {
                    $hash = $el;
                    $c->pullFile($hash,ROOT.DS.'tmp'.DS.$hash);
                    if(!file_exists(ROOT.DS.'tmp'.DS.$hash)) continue;
                    storeFile(ROOT.DS.'tmp'.DS.$hash,$hash,true);

                    if(defined('LOG_VIEWS') && LOG_VIEWS===true)
                        addToLog(getUserIP()."\tviewed\t$hash\tIt was found in Storage Controller $contr. Agent:\t".$_SERVER['HTTP_USER_AGENT']."\tref:\t".$_SERVER['HTTP_REFERER'], ROOT.DS.'logs/views.log');
                    
                    break; // we break here because we already have the file. no need to check other storage controllers
                }
                else if($c->isEnabled()===true && defined('ENCRYPTION_KEY') && ENCRYPTION_KEY !='' && $c->hashExists($el.'.enc')) //this is an encrypted file. Let's decrypt it
                {
                    $hash = $el.'.enc';
                    $c->pullFile($hash,ROOT.DS.'tmp'.DS.$hash);
                    if(!file_exists(ROOT.DS.'tmp'.DS.$hash)) continue;
                    $enc = new Encryption;
                    $hash = substr($hash,0,-4);
                    $enc->decryptFile(ROOT.DS.'tmp'.DS.$el.'.enc', ROOT.DS.'tmp'.DS.$hash,base64_decode(ENCRYPTION_KEY));

                    storeFile(ROOT.DS.'tmp'.DS.$hash,$hash,true);
                    unlink(ROOT.DS.'tmp'.DS.$el.'.enc');

                    if(defined('LOG_VIEWS') && LOG_VIEWS===true)
                        addToLog(getUserIP()."\tviewed\t$hash\tIt was found encrypted in Storage Controller $contr. Agent:\t".$_SERVER['HTTP_USER_AGENT']."\tref:\t".$_SERVER['HTTP_REFERER'], ROOT.DS.'logs/views.log');

                    break; // we break here because we already have the file. no need to check other storage controllers
                }
            }

        }
        // if it's still false, we only have one hope: Maybe it's from a dynamic controller and the cache hasn't been created yet
        else if($hash===false)
        {
            foreach(loadAllContentControllers(true) as $cc)
            {
                if((new $cc)::ctype=='dynamic' &&  in_array((new $cc)->getRegisteredExtensions()[0],$u) )
                {
                    if(defined('LOG_VIEWS') && LOG_VIEWS===true)
                        addToLog(getUserIP()." requested ".implode("/",$u)."\tIt's a dynamic image handled by  $cc. Agent:\t".$_SERVER['HTTP_USER_AGENT']."\tref:\t".$_SERVER['HTTP_REFERER'], ROOT.DS.'logs/views.log');
                    $hash = true;
                    break;
                }
            }
        }
    }


    //we didn't find a hash. send error 404
    if($hash===false)
    {
        http_response_code(404);
        die("404");
    }
    else
    {
        //ok we have a valid hash.

        //is the user requesting this file to be deleted?
        foreach($u as $el)
        {
            if(startsWith($el,'delete_'))
            {
                $code = substr($el,7);
                //@todo: allow MASTER_DELETE_IP to be CIDR range or coma separated
                if(getDeleteCodeOfHash($hash)==$code || (defined('MASTER_DELETE_CODE') && MASTER_DELETE_CODE==$code ) || (defined('MASTER_DELETE_IP') && MASTER_DELETE_IP==getUserIP()) )
                {
                    addToLog(getUserIP()." deleted $hash\tIt was deleted by the user\t".$_SERVER['HTTP_USER_AGENT'], ROOT.DS.'logs/deletes.log');
                    deleteHash($hash);
                    if(isset($GLOBALS['redis']))
                    {
                        $GLOBALS['redis']->del('cache:byurl:'.implode('/',$u));
                        addToLog("Deleting cache for URL \t".implode('/',$u)." because $hash was deleted");
                    }
                    exit($hash.' deleted successfully');
                }
            }
        }
        
        
        //Now let's check the extension to find out which controller will be handling this request
        $extension = pathinfo($hash, PATHINFO_EXTENSION);

        
        foreach(loadAllContentControllers(true) as $cc)
        {
            if( 
                ((new $cc)::ctype=='dynamic' &&  in_array((new $cc)->getRegisteredExtensions()[0],$u)) || 
                ((new $cc)::ctype=='static' && in_array($extension,(new $cc)->getRegisteredExtensions()))
            )
            {
                //cache it so we don't have to check again
                if(isset($GLOBALS['redis']))
                {
                    $GLOBALS['redis']->set('cache:byurl:'.implode('/',$u),"$cc;$hash");
                    addToLog("Caching URL \t".implode('/',$u)."\thash: $hash\tto content controller: $cc");
                    if($hash!==true)
                        $GLOBALS['redis']->incr("served:$hash");
                    else //if it's a dynamic image, we count how many times this url was served
                        $GLOBALS['redis']->incr("served:".implode('/',$u));
                }
                return (new $cc())->handleHash($hash,$u);
            }
        }

        http_response_code(404);
        die("404");
    }

    //var_dump($u);
}

function storageControllerUpload($hash)
{
    // Lets' check all storage controllers and tell them that a new file was uploaded
    $sc = getStorageControllers();
    $allgood = true;
    $uploadedhash =$hash;
    foreach($sc as $contr)
    {
        $controller = new $contr();
        if($controller->isEnabled()===true)
        {
            $source = getDataDir().DS.$hash.DS.$hash;
            if(defined('ENCRYPTION_KEY') && ENCRYPTION_KEY) //ok so we got an encryption key which means we'll store only  the encrypted file
            {
                $enc = new Encryption;
                $encoded_file = ROOT.DS.'tmp'.DS.$hash.'.enc';
                $enc->encryptFile($source,$encoded_file,base64_decode(ENCRYPTION_KEY));
                $controller->pushFile($encoded_file,$hash.'.enc');
                unlink($encoded_file);
                $uploadedhash = $hash.'.enc';
            }
            else // not encrypted
                $controller->pushFile($source,$hash);

            //let's check if the file is really there. If not, queue it for later
            if(!$controller->hashExists($uploadedhash))
            {
                $allgood = false;
                $queuefile=ROOT.DS.'tmp'.DS.'controllerqueue.txt';
                if(!file_exists($queuefile) || !stringInFile($hash,$queuefile))
                {
                    $fp=fopen($queuefile,'a');
                    if($fp)
                    {
                        fwrite($fp,$hash."\n");
                        fclose($fp);
                    }
                }
            }
        }
            
    }

    return $allgood;
}

function stringInFile($string,$file)
{
    $handle = fopen($file, 'r');
    while (($line = fgets($handle)) !== false) {
        $line=trim($line);
        if($line==$string) return true;  
    }
    fclose($handle);
    return false;
}

function getNewHash($type,$length=10)
{
	while(1)
	{
		$hash = getRandomString($length).'.'.$type;
        if(!isExistingHash($hash)) return $hash;
        $length++;
	}
}

function isExistingHash($hash)
{
    if(!trim($hash)) return false;
    return is_dir(getDataDir().DS.$hash);
}

function mightBeAHash($string)
{
	$len = strlen($string);
	$dot = strpos($string,'.');
    if(substr_count($string,'.')!=1) return false;
    if(!$dot) return false;
    $afterdot = substr($string,$dot+1);

    //@todo: maybe pull all allowed types and compare to afterdot
    return ($afterdot && strlen($afterdot)>=2 && strlen($afterdot)<=5 );
}

function autoload($className)
{
    if (file_exists(ROOT . DS . 'src'. DS . 'interfaces' . DS . strtolower($className) . '.interface.php'))
        require_once(ROOT . DS . 'src'. DS . 'interfaces' . DS . strtolower($className) . '.interface.php');
    if ($className=='Encryption')
        require_once(ROOT . DS . 'inc' . DS . 'encryption.php');
}

function renderTemplate($template,$variables=[],$basepath=ROOT.'/src')
{
    ob_start();
    if(is_array($variables))
        extract($variables);
    if(file_exists($basepath.DS.'templates'.DS.$template.'.php'))
        include($basepath.DS.'templates'.DS.$template.'.php');
    else if(file_exists($basepath.DS.'templates'.DS.$template))
        include($basepath.DS.'templates'.DS.$template);
    $rendered = ob_get_contents();
    ob_end_clean();

    return $rendered;
}

function getExtensionOfFilename($file)
{
    return pathinfo($file, PATHINFO_EXTENSION);
}

function sizeStringToWidthHeight($size)
{
	if(!$size || !isSize($size)) return false;
	if(!is_numeric($size))
        $size = explode('x',$size);

    if(is_array($size))
    {
        $maxwidth = $size[0];
        $maxheight = $size[1];
    }
    else if($size)
    {
        $maxwidth = $size;
        $maxheight = $size;
    }
	
	return array('width'=>$maxwidth,'height'=>$maxheight);
}

function sanatizeString($string)
{
    return preg_replace("/[^a-zA-Z0-9._\-]+/", "", $string);
}

function renderSize($byte)
{
    if($byte < 1024) {
        $result = round($byte, 2). ' Byte';
    }elseif($byte < pow(1024, 2)) {
        $result = round($byte/1024, 2).' KB';
    }elseif($byte >= pow(1024, 2) and $byte < pow(1024, 3)) {
        $result = round($byte/pow(1024, 2), 2).' MB';
    }elseif($byte >= pow(1024, 3) and $byte < pow(1024, 4)) {
        $result = round($byte/pow(1024, 3), 2).' GB';
    }elseif($byte >= pow(1024, 4) and $byte < pow(1024, 5)) {
        $result = round($byte/pow(1024, 4), 2).' TB';
    }elseif($byte >= pow(1024, 5) and $byte < pow(1024, 6)) {
        $result = round($byte/pow(1024, 5), 2).' PB';
    }elseif($byte >= pow(1024, 6) and $byte < pow(1024, 7)) {
        $result = round($byte/pow(1024, 6), 2).' EB';
    }

        return $result;
}

function getTypeOfFile($url)
{
    // on linux use the "file" command or it will handle everything as octet-stream
	if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
	{
		$content_type = exec("file -bi " . escapeshellarg($url));
		if($content_type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
			$type = $content_type;
    }
    else
    {
        //for windows we'll use mime_content_type. Make sure you have enabled the "exif" extension in php.ini
        $type = mime_content_type($url);
    }
    if(!$type) return false;
    if(startsWith($type,'text')) return 'text';
	$arr = explode(';', trim($type));
	if(count($arr)>1)
	{
		$a2 = explode('/', $arr[0]);
		$type = $a2[1];
	}
	else
	{
		$a2 = explode('/', $type);
		$type = $a2[1];
    }
    
    if($type=='octet-stream')
        if((new VideoController())->isProperMP4($url)) return 'mp4';
    if($type=='mp4')
        if(!(new VideoController())->isProperMP4($url))
		    return false;
	
	return $type;
}

function isFolderWritable($dir){return is_writable($dir);}

function getRandomString($length=32, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyz')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[rand(0, $max)];
    }
    return $str;
}
function startsWith($haystack,$needle)
{
    $length = strlen($needle);
    return (substr($haystack,0,$length) === $needle);
}
function endswith($string, $test) {
    if(!$string || !$test) return false;
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function getUserIP()
{
    if(isCloudflare() || $_SERVER['HTTP_CF_CONNECTING_IP'])
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];
	
    if($forward != null && strpos($forward,','))
    {
        $a = explode(',',$forward);
        $forward = trim($a[0]);
    }
	if(filter_var($forward, FILTER_VALIDATE_IP))
	{
		$ip = $forward;
	}
    elseif(filter_var($client, FILTER_VALIDATE_IP))
	{
		$ip = $client;
	}
	else
	{
		$ip = $remote;
	}
	return $ip;
}

// checks the list of uploaded files for this hash
function sha1Exists($sha1)
{
    $shafile = getDataDir().DS.'sha1.csv';

    if(!file_exists($shafile)) touch($shafile);
    $handle = fopen($shafile, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if(substr($line,0,40)===$sha1) return trim(substr($line,41));
        }

        fclose($handle);
    }
    return false;
}

//adds new sha to  the hash list
function addSha1($hash,$sha1)
{
    if(!$sha1 || !$hash) return false;
    if(sha1Exists($sha1)) return;
    $fp = fopen(getDataDir().DS.'sha1.csv','a');
    if($fp)
    {
        fwrite($fp,"$sha1;$hash\n");
        fclose($fp);
    }
    return true;
}

function isSize($var)
{
	if(is_numeric($var)) return true;
	$a = explode('x',$var);
	if(count($a)!=2 || !is_numeric($a[0]) || !is_numeric($a[1])) return false;
	
	return true;
}

function isColor($var)
{
    if(strlen($var)==6 && ctype_xdigit($var)) return true;
    else
    {
        $col = color_name_to_hex($var);
        if($col) return true;
        else return false;
    }
}

function color_name_to_hex($color_name)
    {
        // standard 147 HTML color names
        $colors  =  array(
            'aliceblue'=>'F0F8FF',
            'antiquewhite'=>'FAEBD7',
            'aqua'=>'00FFFF',
            'aquamarine'=>'7FFFD4',
            'azure'=>'F0FFFF',
            'beige'=>'F5F5DC',
            'bisque'=>'FFE4C4',
            'black'=>'000000',
            'blanchedalmond '=>'FFEBCD',
            'blue'=>'0000FF',
            'blueviolet'=>'8A2BE2',
            'brown'=>'A52A2A',
            'burlywood'=>'DEB887',
            'cadetblue'=>'5F9EA0',
            'chartreuse'=>'7FFF00',
            'chocolate'=>'D2691E',
            'coral'=>'FF7F50',
            'cornflowerblue'=>'6495ED',
            'cornsilk'=>'FFF8DC',
            'crimson'=>'DC143C',
            'cyan'=>'00FFFF',
            'darkblue'=>'00008B',
            'darkcyan'=>'008B8B',
            'darkgoldenrod'=>'B8860B',
            'darkgray'=>'A9A9A9',
            'darkgreen'=>'006400',
            'darkgrey'=>'A9A9A9',
            'darkkhaki'=>'BDB76B',
            'darkmagenta'=>'8B008B',
            'darkolivegreen'=>'556B2F',
            'darkorange'=>'FF8C00',
            'darkorchid'=>'9932CC',
            'darkred'=>'8B0000',
            'darksalmon'=>'E9967A',
            'darkseagreen'=>'8FBC8F',
            'darkslateblue'=>'483D8B',
            'darkslategray'=>'2F4F4F',
            'darkslategrey'=>'2F4F4F',
            'darkturquoise'=>'00CED1',
            'darkviolet'=>'9400D3',
            'deeppink'=>'FF1493',
            'deepskyblue'=>'00BFFF',
            'dimgray'=>'696969',
            'dimgrey'=>'696969',
            'dodgerblue'=>'1E90FF',
            'firebrick'=>'B22222',
            'floralwhite'=>'FFFAF0',
            'forestgreen'=>'228B22',
            'fuchsia'=>'FF00FF',
            'gainsboro'=>'DCDCDC',
            'ghostwhite'=>'F8F8FF',
            'gold'=>'FFD700',
            'goldenrod'=>'DAA520',
            'gray'=>'808080',
            'green'=>'008000',
            'greenyellow'=>'ADFF2F',
            'grey'=>'808080',
            'honeydew'=>'F0FFF0',
            'hotpink'=>'FF69B4',
            'indianred'=>'CD5C5C',
            'indigo'=>'4B0082',
            'ivory'=>'FFFFF0',
            'khaki'=>'F0E68C',
            'lavender'=>'E6E6FA',
            'lavenderblush'=>'FFF0F5',
            'lawngreen'=>'7CFC00',
            'lemonchiffon'=>'FFFACD',
            'lightblue'=>'ADD8E6',
            'lightcoral'=>'F08080',
            'lightcyan'=>'E0FFFF',
            'lightgoldenrodyellow'=>'FAFAD2',
            'lightgray'=>'D3D3D3',
            'lightgreen'=>'90EE90',
            'lightgrey'=>'D3D3D3',
            'lightpink'=>'FFB6C1',
            'lightsalmon'=>'FFA07A',
            'lightseagreen'=>'20B2AA',
            'lightskyblue'=>'87CEFA',
            'lightslategray'=>'778899',
            'lightslategrey'=>'778899',
            'lightsteelblue'=>'B0C4DE',
            'lightyellow'=>'FFFFE0',
            'lime'=>'00FF00',
            'limegreen'=>'32CD32',
            'linen'=>'FAF0E6',
            'magenta'=>'FF00FF',
            'maroon'=>'800000',
            'mediumaquamarine'=>'66CDAA',
            'mediumblue'=>'0000CD',
            'mediumorchid'=>'BA55D3',
            'mediumpurple'=>'9370D0',
            'mediumseagreen'=>'3CB371',
            'mediumslateblue'=>'7B68EE',
            'mediumspringgreen'=>'00FA9A',
            'mediumturquoise'=>'48D1CC',
            'mediumvioletred'=>'C71585',
            'midnightblue'=>'191970',
            'mintcream'=>'F5FFFA',
            'mistyrose'=>'FFE4E1',
            'moccasin'=>'FFE4B5',
            'navajowhite'=>'FFDEAD',
            'navy'=>'000080',
            'oldlace'=>'FDF5E6',
            'olive'=>'808000',
            'olivedrab'=>'6B8E23',
            'orange'=>'FFA500',
            'orangered'=>'FF4500',
            'orchid'=>'DA70D6',
            'palegoldenrod'=>'EEE8AA',
            'palegreen'=>'98FB98',
            'paleturquoise'=>'AFEEEE',
            'palevioletred'=>'DB7093',
            'papayawhip'=>'FFEFD5',
            'peachpuff'=>'FFDAB9',
            'peru'=>'CD853F',
            'pink'=>'FFC0CB',
            'plum'=>'DDA0DD',
            'powderblue'=>'B0E0E6',
            'purple'=>'800080',
            'red'=>'FF0000',
            'rosybrown'=>'BC8F8F',
            'royalblue'=>'4169E1',
            'saddlebrown'=>'8B4513',
            'salmon'=>'FA8072',
            'sandybrown'=>'F4A460',
            'seagreen'=>'2E8B57',
            'seashell'=>'FFF5EE',
            'sienna'=>'A0522D',
            'silver'=>'C0C0C0',
            'skyblue'=>'87CEEB',
            'slateblue'=>'6A5ACD',
            'slategray'=>'708090',
            'slategrey'=>'708090',
            'snow'=>'FFFAFA',
            'springgreen'=>'00FF7F',
            'steelblue'=>'4682B4',
            'tan'=>'D2B48C',
            'teal'=>'008080',
            'thistle'=>'D8BFD8',
            'tomato'=>'FF6347',
            'turquoise'=>'40E0D0',
            'violet'=>'EE82EE',
            'wheat'=>'F5DEB3',
            'white'=>'FFFFFF',
            'whitesmoke'=>'F5F5F5',
            'yellow'=>'FFFF00',
            'yellowgreen'=>'9ACD32');

        $color_name = strtolower($color_name);
        if (isset($colors[$color_name]))
            return $colors[$color_name];
        else
            return false;
    }

function getStorageControllers()
{
    $controllers = array();
    if ($handle = opendir(ROOT.DS.'storage-controllers')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if(endswith($entry,'.controller.php'))
                {
                    $controllers[] = ucfirst(substr($entry,0,-15)).'Storage';
                    include_once(ROOT.DS.'storage-controllers'.DS.$entry);
                }
            }
        }
        closedir($handle);
    }

    return $controllers;
}

function loadAllContentControllers($all=false)
{
    $allowedcontrollers = false;
    if(defined('CONTENTCONTROLLERS') && CONTENTCONTROLLERS != '' && $all!==true)
    {
        $allowedcontrollers = array_map('strtolower', explode(',',CONTENTCONTROLLERS));
    }
    $controllers = array();
    if ($handle = opendir(ROOT.DS.'src'.DS.'content-controllers')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if(
                    is_dir(ROOT.DS.'src'.DS.'content-controllers'.DS.$entry) &&
                    file_exists(ROOT.DS.'src'.DS.'content-controllers'.DS.$entry.DS."$entry.controller.php") &&
                    (
                        ($allowedcontrollers!==false && in_array($entry,$allowedcontrollers) )
                        ||
                        $allowedcontrollers===false)
                    )
                {
                    $controllers[] = ucfirst($entry).'Controller';
                    require_once(ROOT.DS.'src'.DS.'content-controllers'.DS.$entry.DS."$entry.controller.php");
                }
            }
        }
        closedir($handle);
    }

    return $controllers;
}

function getAllContentFiletypes()
{
    $types = array();
    $controllers = loadAllContentControllers();
    foreach($controllers as $c)
    {
        $instance = new $c;
        if($instance::ctype=='static')
            $types = array_merge($types,(new $instance)->getRegisteredExtensions());
    }

    return $types;
}

function rrmdir($dir) { 
    chmod($dir, 0777);
    if (is_dir($dir)) { 
      $objects = scandir($dir); 
      foreach ($objects as $object) { 
        if ($object != "." && $object != "..") { 
          if (is_dir($dir."/".$object))
            rrmdir($dir."/".$object);
          else
          {
            unlink($dir."/".$object); 
          }
        } 
      }
      rmdir($dir); 
    } 
  }

function storeFile($srcfile,$hash,$deleteoriginal=false)
{
    if(is_dir(getDataDir().DS.$hash) && file_exists(getDataDir().DS.$hash.DS.$hash)) return;
    mkdir(getDataDir().DS.$hash);
	$file = getDataDir().DS.$hash.DS.$hash;
	
    copy($srcfile, $file);
    if($deleteoriginal===true)
        unlink($srcfile);

    addSha1($hash,sha1_file($file));
       
    if(defined('LOG_UPLOADER') && LOG_UPLOADER)
	{
		$fh = fopen(getDataDir().DS.'uploads.csv', 'a');
		fwrite($fh, time().';;'.$hash.';'.getUserIP()."\n");
		fclose($fh);
	}

    return $file;
}

function getDeleteCodeOfHash($hash)
{
    $metadata = getMetadataOfHash($hash);
    if($metadata && isset($metadata['delete_code']))
        return $metadata['delete_code'];
    else
        return false;
}

function getMetadataOfHash($hash)
{
    $metadata = [];
    $metadatafile = getDataDir().DS.$hash.DS.'meta.json';
    if(file_exists($metadatafile))
    {
        $json = file_get_contents($metadatafile);
        $metadata = json_decode($json,true);
    }
    return $metadata;
}

function deleteHash($hash)
{
    //@todo: add hash to deleted list. also on all controllers

    //delete all files in directory
    rrmdir(getDataDir().DS.$hash);

    //tell every storage controller to delete theirs as well
    $sc = getStorageControllers();
    foreach($sc as $contr)
    {
        $c = new $contr();
        if($c->isEnabled()===true && $c->hashExists($hash)) 
        {
            $c->deleteFile($hash);
        }
        //delete encrypted file if it exists
        if($c->isEnabled()===true && defined('ENCRYPTION_KEY') && ENCRYPTION_KEY !='' && $c->hashExists($hash.'.enc'))
        {
            $c->deleteFile($hash.'.enc');
        }
    }
}

/**
 * Check if a given IPv4 or IPv6 is in a network
 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, or 2001:db8::8a2e:370:7334/128
 * @return boolean true if the ip is in this range / false if not.
 * via https://stackoverflow.com/a/56050595/1174516
 */
function isIPInRange( $ip, $range ) {

    if(strpos($range,',')!==false)
    {
        // we got a list of ranges. splitting
        $ranges = array_map('trim',explode(',',$range));
        foreach($ranges as $range)
            if(isIPInRange($ip,$range)) return true;
        return false;
    }
    // Get mask bits
    list($net, $maskBits) = explode('/', $range);

    // Size
    $size = (strpos($ip, ':') === false) ? 4 : 16;

    // Convert to binary
    $ip = inet_pton($ip);
    $net = inet_pton($net);
    if (!$ip || !$net) {
        throw new InvalidArgumentException('Invalid IP address');
    }

    // Build mask
    $solid = floor($maskBits / 8);
    $solidBits = $solid * 8;
    $mask = str_repeat(chr(255), $solid);
    for ($i = $solidBits; $i < $maskBits; $i += 8) {
        $bits = max(0, min(8, $maskBits - $i));
        $mask .= chr((pow(2, $bits) - 1) << (8 - $bits));
    }
    $mask = str_pad($mask, $size, chr(0));

    // Compare the mask
    return ($ip & $mask) === ($net & $mask);
}

function loadContentControllers()
{
    if(defined('CONTENTCONTROLLERS') && CONTENTCONTROLLERS != '')
    {
        $controllers = explode(',',CONTENTCONTROLLERS);
        foreach($controllers as $controller)
        {
            $controller = strtolower($controller);
            if(@file_exists(ROOT . DS . 'content-controllers' . DS. $controller. DS . $controller.'.controller.php'))
                require_once(ROOT . DS . 'content-controllers' . DS. $controller. DS . $controller.'.controller.php');
        }
    }
    else
        loadAllContentControllers();
}

function ip_in_range($ip, $range) {
    if (strpos($range, '/') == false)
        $range .= '/32';

    // $range is in IP/CIDR format eg 127.0.0.1/24
    list($range, $netmask) = explode('/', $range, 2);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}


// from https://www.cloudflare.com/ips-v4
// and  https://www.cloudflare.com/ips-v6
function _cloudflare_CheckIP($ip) {
    $cf_ips = array_filter(array_map('trim',explode("\n","
    173.245.48.0/20
    103.21.244.0/22
    103.22.200.0/22
    103.31.4.0/22
    141.101.64.0/18
    108.162.192.0/18
    190.93.240.0/20
    188.114.96.0/20
    197.234.240.0/22
    198.41.128.0/17
    162.158.0.0/15
    104.16.0.0/13
    104.24.0.0/14
    172.64.0.0/13
    131.0.72.0/22
    2400:cb00::/32
    2606:4700::/32
    2803:f800::/32
    2405:b500::/32
    2405:8100::/32
    2a06:98c0::/29
    2c0f:f248::/32
    ")));

    $is_cf_ip = false;
    foreach ($cf_ips as $cf_ip) {
        if (ip_in_range($ip, $cf_ip)) {
            $is_cf_ip = true;
            break;
        }
    } return $is_cf_ip;
}

function _cloudflare_Requests_Check() {
    $flag = true;

    if(!isset($_SERVER['HTTP_CF_CONNECTING_IP']))   $flag = false;
    if(!isset($_SERVER['HTTP_CF_IPCOUNTRY']))       $flag = false;
    if(!isset($_SERVER['HTTP_CF_RAY']))             $flag = false;
    if(!isset($_SERVER['HTTP_CF_VISITOR']))         $flag = false;
    return $flag;
}

function isCloudflare() {
    $ipCheck        = _cloudflare_CheckIP($_SERVER['REMOTE_ADDR']);
    $requestCheck   = _cloudflare_Requests_Check();
    return ($ipCheck && $requestCheck);
}

function executeUploadPermission()
{
    if(defined('ALLOWED_SUBNET') && ALLOWED_SUBNET != '' && !isIPInRange( getUserIP(), ALLOWED_SUBNET ))
    {
        http_response_code(403);
        exit(json_encode(array('status'=>'err','reason'=> 'Access denied')));
    }
    else if(defined('UPLOAD_CODE') && UPLOAD_CODE!='')
    {
        if(!isset($_REQUEST['uploadcode']) || $_REQUEST['uploadcode']!=UPLOAD_CODE)
        {
            http_response_code(403);
            exit(json_encode(array('status'=>'err','reason'=> 'Incorrect upload code specified - Access denied')));
        }
    }
}

/**
 * Checks if a URL is valid
 * @param string $url
 * @return boolean (true if valid, false if not)
 */
function checkURLForPrivateIPRange($url)
{
    $host = getHost($url);
    $ip = gethostbyname($host);
    if(is_public_ipv4($ip) || is_public_ipv6($ip)) return false;
    return true;
}

function getHost($url){ 
    $URIs = parse_url(trim($url)); 
    $host = !empty($URIs['host'])? $URIs['host'] : explode('/', $URIs['path'])[0];
    return $host;  
} 

function is_public_ipv4($ip=NULL)
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === $ip ? TRUE : FALSE;
}

function is_public_ipv6($ip=NULL)
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === $ip ? TRUE : FALSE;
}

function getDataDir()
{
    if (defined('_TEST_DATA_OVERRIDE')) return _TEST_DATA_OVERRIDE;  // test isolation
    if(defined('SPLIT_DATA_DIR') && SPLIT_DATA_DIR===true && getDomain() && in_array(getDomain(),explode(',',ALLOWED_DOMAINS)))
    {
        $dir = ROOT.DS.'data'.DS.getDomain();
        if(!is_dir($dir)) mkdir($dir);
        return $dir;
    }
    return ROOT.DS.'data';
}

function getDomain($stripport=true)
{
    $host = $_SERVER['HTTP_HOST'];
    //strip port
    if(strpos($host,':')!==false)
        $strippedhost = substr($host,0,strpos($host,':'));
    else $strippedhost = $host;

    //check if it's in ALLOWED_DOMAINS
    if(defined('ALLOWED_DOMAINS') && ALLOWED_DOMAINS!='')
    {
        $domains = explode(',',ALLOWED_DOMAINS);
        if(!in_array($strippedhost,$domains)) //always check without port
            return false;
        else return ($stripport ? $strippedhost : $host);
    }
    else if(!defined('ALLOWED_DOMAINS')){
        //if not defined, we can use any domain
        return ($stripport ? $strippedhost : $host);
    }
    else return false;
}

function getURL()
{
    if(defined('URL') && URL !='')
        return URL;
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http' : 'https';
    return $protocol . '://' . getDomain(false).'/';
}

function updateMetaData($hash, $meta)
{
    $metaFile = getDataDir() . DS . $hash . DS . 'meta.json';
    if (file_exists($metaFile)) {
        $currentMeta = json_decode(file_get_contents($metaFile), true);
        $newMeta = array_merge($currentMeta, $meta);
        file_put_contents($metaFile, json_encode($newMeta));
    } else {
        file_put_contents($metaFile, json_encode($meta));
    }
}

function addToLog($data,$logfile=ROOT.DS.'logs/app.log')
{
    $fp = fopen($logfile,'a');
    fwrite($fp,date("d.m.y H:i")."\t[".getURL()."] | ".$data."\n");
    fclose($fp);
}

function isCacheStale(): bool
{
    if (!isset($GLOBALS['redis']) || !$GLOBALS['redis']) return true;
    $builtAt = $GLOBALS['redis']->get('stats:built_at');
    if (!$builtAt) return true;
    return (time() - (int)$builtAt) > 300;
}

function rebuildStatsCache(): void
{
    if (!isset($GLOBALS['redis']) || !$GLOBALS['redis']) return;

    $dirs = glob(getDataDir().DS.'*', GLOB_ONLYDIR);
    if (empty($dirs)) {
        $GLOBALS['redis']->del('stats:index');
        $GLOBALS['redis']->set('stats:built_at', (string)time());
        return;
    }

    $hashes = array_map('basename', $dirs);

    // Batch-fetch all view counts in one MGET instead of N individual GETs
    $viewKeys = array_map(fn($h) => 'served:'.$h, $hashes);
    $viewValues = $GLOBALS['redis']->mget($viewKeys);
    $views = array_combine($hashes, $viewValues);

    // Write all entries through a pipeline to minimize round-trips
    $GLOBALS['redis']->del('stats:index');
    $pipe = $GLOBALS['redis']->pipeline();
    foreach ($dirs as $dir) {
        $hash = basename($dir);
        $meta = getMetadataOfHash($hash);
        $file = $dir.DS.$hash;
        $pipe->hset('stats:index', $hash, json_encode([
            'hash'              => $hash,
            'views'             => (int)($views[$hash] ?? 0),
            'mime'              => $meta['mime'] ?? '',
            'ip'                => $meta['ip'] ?? '',
            'uploaded'          => $meta['uploaded'] ?? 0,
            'original_filename' => $meta['original_filename'] ?? '',
            'size'              => file_exists($file) ? filesize($file) : 0,
        ]));
    }
    $pipe->execute();

    $GLOBALS['redis']->set('stats:built_at', (string)time());
}

function getStatsPage(int $page, string $sort, string $dir, string $q): array
{
    $allowedSorts = ['views', 'uploaded', 'mime', 'size', 'hash', 'original_filename', 'ip'];
    if (!in_array($sort, $allowedSorts, true)) $sort = 'uploaded';
    if (!in_array($dir, ['asc', 'desc'], true)) $dir = 'desc';
    $page = max(1, $page);

    $rows = [];
    if (isset($GLOBALS['redis']) && $GLOBALS['redis']) {
        $raw = $GLOBALS['redis']->hgetall('stats:index');
        foreach ($raw as $hash => $json) {
            $entry = json_decode($json, true);
            if (!$entry) continue;
            $entry['hash'] = $hash;
            $rows[] = $entry;
        }
    }

    // Filter
    if ($q !== '') {
        $q = strtolower($q);
        $rows = array_values(array_filter($rows, function($r) use ($q) {
            return str_contains(strtolower($r['hash'] ?? ''), $q)
                || str_contains(strtolower($r['original_filename'] ?? ''), $q)
                || str_contains(strtolower($r['ip'] ?? ''), $q)
                || str_contains(strtolower($r['mime'] ?? ''), $q);
        }));
    }

    // Sort
    usort($rows, function($a, $b) use ($sort, $dir) {
        $av = $a[$sort] ?? 0;
        $bv = $b[$sort] ?? 0;
        $cmp = is_numeric($av) && is_numeric($bv)
            ? ($av <=> $bv)
            : strcmp((string)$av, (string)$bv);
        return $dir === 'asc' ? $cmp : -$cmp;
    });

    $total = count($rows);
    $pageSize = 50;
    $totalPages = $total > 0 ? (int)ceil($total / $pageSize) : 0;
    $page = min($page, max(1, $totalPages));
    $rows = array_slice($rows, ($page - 1) * $pageSize, $pageSize);

    return [
        'rows'        => $rows,
        'total'       => $total,
        'page'        => $page,
        'total_pages' => $totalPages,
        'sort'        => $sort,
        'dir'         => $dir,
        'q'           => $q,
    ];
}

function getLogs($type='app',$filter=false)
{
    $logs = array();
    $logfile = ROOT.DS.'logs/'.$type.'.log';
    if($type && file_exists($logfile))
    {
        $handle = fopen($logfile, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if($filter && strpos($line,$filter)===false) continue;
                $logs[] = $line;
            }
            fclose($handle);
        }
    }
    return $logs;
}

function getAllHashes()
{
    $hashes = [];
    foreach (glob(getDataDir().DS.'*') as $dir) {
        if (is_dir($dir) && file_exists($dir.DS.basename($dir))) {
            $hashes[] = basename($dir);
        }
    }
    return $hashes;
}

function rebuildMeta(){
    $hashes = getAllHashes();
    $o = '';

    foreach($hashes as $h){
        $o.= "[i] $h\n";
        $metaFile = getDataDir() . DS . $h . DS . 'meta.json';
        $metadata = [];
        if (file_exists($metaFile)) {
            $metadata = json_decode(file_get_contents($metaFile), true);
        }

        if(!$metadata['mime'])
        {
            $metadata['mime'] = getFileMimeType(getDataDir() . DS . $h . DS . $h);
            $o.= "  [+] Added MIME type: ".$metadata['mime']."\n";
        }
        if(!$metadata['size'])
        {
            $metadata['size'] = filesize(getDataDir() . DS . $h . DS . $h);
            $metadata['size_human'] = renderSize($metadata['size']);

            $o.= "  [+] Added size: ".$metadata['size']." (".$metadata['size_human'].")\n";
        }
        if(!$metadata['uploaded'])
        {
            $metadata['uploaded'] = filectime(getDataDir() . DS . $h . DS . $h);
            $o.= "  [+] Restored creation time from file parameter: ".$metadata['uploaded']." -> ".date('d.m.y H:i',$metadata['uploaded'])."\n";
        }
        if(!$metadata['hash'])
        {
            $metadata['hash'] = $h;
            $o.= "  [+] Added hash\n";
        }
        if(!$metadata['delete_code'])
        {
            if(file_exists(getDataDir() . DS . $h . DS . 'deletecode'))
            {
                $metadata['delete_code'] = file_get_contents(getDataDir() . DS . $h . DS . 'deletecode');
                unlink(getDataDir() . DS . $h . DS . 'deletecode');
                $metadata['delete_url'] = getURL().'delete_'.$metadata['delete_code'].'/'.$h;
                $o.= "  [+] Imported old style delete code and updated delete_url to ".$metadata['delete_url']."\n";
            }
            else{
                $metadata['delete_code'] = getRandomString(32);
                $metadata['delete_url'] = getURL().'delete_'.$metadata['delete_code'].'/'.$h;
                $o.= "  [+] Created new delete code and updated delete_url to ".$metadata['delete_url']."\n";
            }
        }
        if(!$metadata['ip'])
        {
            if(file_exists(getDataDir() . DS . 'uploads.csv'))
            {
                $handle = fopen(getDataDir() . DS . 'uploads.csv', "r");
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        if(strpos($line,$h)===false) continue;
                        $a = explode(';',$line);
                        $time = $a[0];
                        $hash = $a[2];
                        $ip = $a[3];
                        if($hash!=$h) continue;
                        $metadata['ip'] = $ip;
                        $metadata['uploaded'] = $time;
                        $o.= "  [+] Found uploader ip in uploads.csv: ".$ip."\tupload time: ".date('d.m.y H:i',$time)."\n";
                        break;
                    }
                    fclose($handle);
                }
            }
        }

        file_put_contents($metaFile, json_encode($metadata));
    }

    return $o;
}

function getFileMimeType($file)
{
    try {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($file);
    } catch (Exception $e) {
        //fallback to shell command if finfo is not available
        $mimeType = shell_exec('file --mime-type -b ' . escapeshellarg($file));
        return trim($mimeType);
    }
}

function getRelativeToDataPath(string $path): string
{
    // Resolve real paths
    $realBase = realpath(ROOT.DS.'data');
    $realPath = realpath($path);

    if ($realBase === false || $realPath === false) {
        throw new InvalidArgumentException("Invalid path or base directory: $path, ".ROOT.DS.'data');
    }

    $baseParts = explode(DIRECTORY_SEPARATOR, trim($realBase, DIRECTORY_SEPARATOR));
    $pathParts = explode(DIRECTORY_SEPARATOR, trim($realPath, DIRECTORY_SEPARATOR));

    // Find common path length
    $i = 0;
    while (isset($baseParts[$i], $pathParts[$i]) && $baseParts[$i] === $pathParts[$i]) {
        $i++;
    }

    // How many directories to go up from base
    $upDirs = count($baseParts) - $i;
    $relativeParts = array_merge(array_fill(0, $upDirs, '..'), array_slice($pathParts, $i));

    return implode('/', $relativeParts);
}

function serveFile($path){
    $relativePath = getRelativeToDataPath($path);
    //since x-accel-redirect does not support paths outside its root, we need to check if the path is relative or absolute
    if(startsWith($relativePath,'..'))
        readfile($path);
    else
        header('X-Accel-Redirect: '. $relativePath);
}

function getReports(){
    $reports = [];
    $reportFile = getDataDir().DS.'reports.json';
    if(file_exists($reportFile)){
        $reports = json_decode(file_get_contents($reportFile), true);
    }
    return $reports;
}

function addReport($hashes, $note){
    if(!$hashes || count($hashes)===0){
        return false;
    }
    $reports = getReports();
    $report['id'] = uniqid();
    $report['timestamp'] = time();
    $report['hashes'] = is_array($hashes) ? $hashes : [$hashes];
    $report['note'] = $note;
    $report['status'] = 'open';
    $report['ip'] = getUserIP();
    $reports[] = $report;
    file_put_contents(getDataDir().DS.'reports.json', json_encode($reports, JSON_PRETTY_PRINT));
    return $report['id'];
}