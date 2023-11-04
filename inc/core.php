<?php 
spl_autoload_register('autoload');

//disable output buffering
if (ob_get_level()) ob_end_clean();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if(!defined('FFMPEG_BINARY'))
    define('FFMPEG_BINARY',ROOT.DS.'bin'.DS.'ffmpeg');

/**
 * The Architect function is the main controller 
 * who will decide what to do with any given URL
 * by feeding it to the other controllers
 */
function architect($url)
{
    //let's get the parts of the URL as array
    //and clean out the empty elements
    $u = array_filter(explode('/', $url));

    //if there is no info in the URL, don't even bother checking with the controllers
    //just show the site
    if( ( (!defined('UPLOAD_FORM_LOCATION') || (defined('UPLOAD_FORM_LOCATION') && !UPLOAD_FORM_LOCATION)) && count($u)==0) || (defined('UPLOAD_FORM_LOCATION') && UPLOAD_FORM_LOCATION && '/'.implode('/',$u)==UPLOAD_FORM_LOCATION) )
    {
        // check if client address is allowed
        $forbidden = false;
        if(defined('ALLOWED_SUBNET') && ALLOWED_SUBNET != '' && !isIPInRange( getUserIP(), ALLOWED_SUBNET ))
        {
            $forbidden = true;
        }
        renderTemplate('main',array('forbidden'=>$forbidden));
        return;
    }

    //check all elements for a valid hash
    $hash = false;
    foreach($u as $el)
    {
        if(isExistingHash($el))
        {
            $hash = $el;
            break;
        }
        // if we don't have a hash yet but the element looks like it could be a hash
        if($hash === false && mightBeAHash($el))
        {
            if(!$sc)
                $sc = getStorageControllers();
            foreach($sc as $contr)
            {                    
                $c = new $contr();

                if($c->isEnabled()===true && $c->hashExists($el))
                {
                    $hash = $el;
                    $c->pullFile($hash,ROOT.DS.'tmp'.DS.$hash);
                    if(!file_exists(ROOT.DS.'tmp'.DS.$hash)) continue;
                    storeFile(ROOT.DS.'tmp'.DS.$hash,$hash,true);
                    
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
                    deleteHash($hash);
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
                (new $cc())->handleHash($hash,$u);
                return;
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
    if (file_exists(ROOT . DS . 'interfaces' . DS . strtolower($className) . '.interface.php'))
        require_once(ROOT . DS . 'interfaces' . DS . strtolower($className) . '.interface.php');
    if ($className=='Encryption')
        require_once(ROOT . DS . 'inc' . DS . 'encryption.php');
}

function renderTemplate($template,$vars=false)
{
    if(is_array($vars))
        extract($vars);
    include_once(ROOT.DS.'templates'.DS.$template.'.html');
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

//
// from: https://stackoverflow.com/questions/25975943/php-serve-mp4-chrome-provisional-headers-are-shown-request-is-not-finished-ye
//
function serveFile($filename, $filename_output = false, $mime = 'application/octet-stream')
{
    $buffer_size = 8192;
    $expiry = 90; //days
    if(!file_exists($filename))
    {
        throw new Exception('File not found: ' . $filename);
    }
    if(!is_readable($filename))
    {
        throw new Exception('File not readable: ' . $filename);
    }
    header_remove('Cache-Control');
    header_remove('Pragma');
    $byte_offset = 0;
    $filesize_bytes = $filesize_original = filesize($filename);
    header('Accept-Ranges: bytes', true);
    header('Content-Type: ' . $mime, true);

    header("Content-Disposition: inline;");
    // Content-Range header for byte offsets
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('%bytes=(\d+)-(\d+)?%i', $_SERVER['HTTP_RANGE'], $match))
    {
        $byte_offset = (int) $match[1];//Offset signifies where we should begin to read the file            
        if (isset($match[2]))//Length is for how long we should read the file according to the browser, and can never go beyond the file size
        {
            $filesize_bytes = min((int) $match[2], $filesize_bytes - $byte_offset);
        }
        header("HTTP/1.1 206 Partial content");
        header(sprintf('Content-Range: bytes %d-%d/%d', $byte_offset, $filesize_bytes - 1, $filesize_original)); ### Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent
    }
    $byte_range = $filesize_bytes - $byte_offset;
    header('Content-Length: ' . $byte_range);
    header('Expires: ' . date('D, d M Y H:i:s', time() + 60 * 60 * 24 * $expiry) . ' GMT');
    $buffer = '';
    $bytes_remaining = $byte_range;
    $handle = fopen($filename, 'r');
    if(!$handle)
    {
        throw new Exception("Could not get handle for file: " .  $filename);
    }
    if (fseek($handle, $byte_offset, SEEK_SET) == -1)
    {
        throw new Exception("Could not seek to byte offset %d", $byte_offset);
    }
    while ($bytes_remaining > 0)
    {
        $chunksize_requested = min($buffer_size, $bytes_remaining);
        $buffer = fread($handle, $chunksize_requested);
        $chunksize_real = strlen($buffer);
        if ($chunksize_real == 0)
        {
            break;
        }
        $bytes_remaining -= $chunksize_real;
        echo $buffer;
        flush();
    }
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
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
}

function getUserIP()
{
    if(isCloudflare())
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
	$client  = @$_SERVER['HTTP_CLIENT_IP'];
	$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	$remote  = $_SERVER['REMOTE_ADDR'];
	
    if(strpos($forward,','))
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
    if(sha1Exists($sha1)) return;
    $fp = fopen(getDataDir().DS.'sha1.csv','a');
    fwrite($fp,"$sha1;$hash\n");
    fclose($fp);
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
    if ($handle = opendir(ROOT.DS.'content-controllers')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if(is_dir(ROOT.DS.'content-controllers'.DS.$entry) && file_exists(ROOT.DS.'content-controllers'.DS.$entry.DS."$entry.controller.php") && ( ($allowedcontrollers!==false && in_array($entry,$allowedcontrollers) ) || $allowedcontrollers===false))
                {
                    $controllers[] = ucfirst($entry).'Controller';
                    include_once(ROOT.DS.'content-controllers'.DS.$entry.DS."$entry.controller.php");
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

    //creating a delete code
    $deletecode = getRandomString(32);
    $fh = fopen(getDataDir().DS.$hash.DS.'deletecode', 'w');
	fwrite($fh, $deletecode);
	fclose($fh);
       
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
    if(file_exists(getDataDir().DS.$hash.DS.'deletecode'))
        return file_get_contents(getDataDir().DS.$hash.DS.'deletecode');
    return false;
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
    else return false;
}

function getURL()
{
    if(defined('URL') && URL !='')
        return URL;
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === FALSE ? 'http' : 'https';
    return $protocol . '://' . getDomain(false).'/';
}
