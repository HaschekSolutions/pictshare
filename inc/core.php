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
    if(count($u)==0)
    {
        renderTemplate('main',false);
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
                    $c->pullFile($el);
                    $hash = $el;
                    break; // we brake here because we already have the file. no need to check other storage controllers
                }
            }
        } 
    }

    //we didn't find a hash. Well let's just display the webpage instead
    if($hash===false)
    {
        //var_dump("main site");
        renderTemplate('main',false);
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

        
        //First, check if URL is an image
        if(in_array($extension,(new ImageController)->getRegisteredExtensions()))
        {
            (new ImageController())->handleHash($hash,$u);
        }
        //or, a url
        else if(in_array($extension,(new UrlController)->getRegisteredExtensions()))
        {
            var_dump("Url");
        }
        //or, a text
        else if(in_array($extension,(new TextController)->getRegisteredExtensions()))
        {
            (new TextController())->handleHash($hash,$u);
        }
        //or, a video
        else if(in_array($extension,(new VideoController)->getRegisteredExtensions()))
        {
            (new VideoController())->handleHash($hash,$u);
        }
        //very odd. We know it's a valid hash but no controller says it's one of their kids
        //oh well
        else
        {
            var_dump("odd err");
        }

    }

    //var_dump($u);
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
	if (file_exists(ROOT . DS . 'content-controllers' . DS . strtolower($className) . '.php'))
        require_once(ROOT . DS . 'content-controllers' . DS . strtolower($className) . '.php');
    if (file_exists(ROOT . DS . 'interfaces' . DS . strtolower($className) . '.interface.php'))
		require_once(ROOT . DS . 'interfaces' . DS . strtolower($className) . '.interface.php');
}

function renderTemplate($template,$vars=false)
{
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
    $fi = new finfo(FILEINFO_MIME);
    $type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));
    
    // on linux use the "file" command or it will handle everything as octet-stream
	if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && startsWith($type,'application/octet-stream'))
	{
		$content_type = exec("file -bi " . escapeshellarg($url));
		if($content_type && $content_type!=$type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
			$type = $content_type;
    }
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
    
    if($type=='octet-stream' && (new VideoController())->isProperMP4($url)) return 'mp4';
	if($type=='mp4' && !(new VideoController())->isProperMP4($url))
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
            if(substr($line,0,40)==$sha1) return trim(substr($line,41));
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

function getAllContentControllers()
{
    $controllers = array();
    if ($handle = opendir(ROOT.DS.'content-controllers')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if(is_dir(ROOT.DS.'content-controllers'.DS.$entry) && file_exists(ROOT.DS.'content-controllers'.DS.$entry.DS."$entry.controller.php"))
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
    $controllers = getAllContentControllers(true);
    foreach($controllers as $c)
    {
        $types = array_merge($types,(new $c)->getRegisteredExtensions());
    }

    return $types;
}

function rrmdir($dir) { 
    if (is_dir($dir)) { 
      $objects = scandir($dir); 
      foreach ($objects as $object) { 
        if ($object != "." && $object != "..") { 
          if (is_dir($dir."/".$object))
            rrmdir($dir."/".$object);
          else
            unlink($dir."/".$object); 
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
		$fh = fopen(ROOT.DS.'data'.DS.'uploads.txt', 'a');
		fwrite($fh, time().';'.$url.';'.$hash.';'.getUserIP()."\n");
		fclose($fh);
	}
}

function getDeleteCodeOfHash($hash)
{
    return file_get_contents(ROOT.DS.'data'.DS.$hash.DS.'deletecode');
}

function deleteHash($hash)
{
    //delete all local images
    rrmdir(ROOT.DS.'data'.DS.$hash);

    //tell every storage controller to delete theirs as well
    $sc = getStorageControllers();
    foreach($sc as $contr)
    {
        $c = new $contr();
        if($c->isEnabled()===true && $c->hashExists($el)) 
        {
            $c->deleteFile($el);
        }
    }
}