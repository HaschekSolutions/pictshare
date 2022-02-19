<?php 
spl_autoload_register('autoload');

//disable output buffering
if (ob_get_level()) ob_end_clean();

error_reporting(E_ALL & ~E_NOTICE);

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
            if(in_array($extension,(new $cc)->getRegisteredExtensions()))
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
            $source = ROOT.DS.'data'.DS.$hash.DS.$hash;
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
    return is_dir(ROOT.DS.'data'.DS.$hash);
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
		if($content_type && $content_type!=$type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
			$type = $content_type;
    }
    else
    {
        $fi = new finfo(FILEINFO_MIME);
        $type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));
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
    $handle = fopen(ROOT.DS.'data'.DS.'sha1.csv', "r");
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
    $fp = fopen(ROOT.DS.'data'.DS.'sha1.csv','a');
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
        $types = array_merge($types,(new $c)->getRegisteredExtensions());
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
    if(is_dir(ROOT.DS.'data'.DS.$hash) && file_exists(ROOT.DS.'data'.DS.$hash.DS.$hash)) return;
    mkdir(ROOT.DS.'data'.DS.$hash);
	$file = ROOT.DS.'data'.DS.$hash.DS.$hash;
	
    copy($srcfile, $file);
    if($deleteoriginal===true)
        unlink($srcfile);

    addSha1($hash,sha1_file($file));

    //creating a delete code
    $deletecode = getRandomString(32);
    $fh = fopen(ROOT.DS.'data'.DS.$hash.DS.'deletecode', 'w');
	fwrite($fh, $deletecode);
	fclose($fh);
       
    if(defined('LOG_UPLOADER') && LOG_UPLOADER)
	{
		$fh = fopen(ROOT.DS.'data'.DS.'uploads.csv', 'a');
		fwrite($fh, time().';'.$url.';'.$hash.';'.getUserIP()."\n");
		fclose($fh);
	}

    return $file;
}

function getDeleteCodeOfHash($hash)
{
    if(file_exists(ROOT.DS.'data'.DS.$hash.DS.'deletecode'))
        return file_get_contents(ROOT.DS.'data'.DS.$hash.DS.'deletecode');
    return false;
}

function deleteHash($hash)
{
    //@todo: add hash to deleted list. also on all controllers

    //delete all files in directory
    rrmdir(ROOT.DS.'data'.DS.$hash);

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
    2400:cb00::/32
    2606:4700::/32
    2803:f800::/32
    2405:b500::/32
    2405:8100::/32
    2a06:98c0::/29
    2c0f:f248::/32
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
}
