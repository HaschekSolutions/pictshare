<?php 

class VideoController
{
    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('mp4','ogg','webm');}

    public function handleHash($hash,$url)
    {
        $path = ROOT.DS.'data'.DS.$hash.DS.$hash;

        //@todo: - resize by changing $path
        //       - preview images

        if(in_array('raw',$url))
            $this->serveMP4($path,$hash);
        else if(in_array('download',$url))
        {
            if (file_exists($path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($path).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                readfile($path);
                exit;
            }
        }
        else
        {
            $data = array('url'=>implode('/',$url),'hash'=>$hash,'filesize'=>renderSize(filesize($path)),'content'=>htmlentities(file_get_contents($path)));
            renderTemplate('video',$data);
        }
    }

    public function handleUpload($tmpfile,$hash=false)
    {
        if($hash===false)
            $hash = getNewHash('mp4',6);

        mkdir(ROOT.DS.'data'.DS.$hash);
		$file = ROOT.DS.'data'.DS.$hash.DS.$hash;
		
        move_uploaded_file($tmpfile, $file);

        if(!$this->rightEncodedMP4($file))
            system("nohup php ".ROOT.DS.'tools'.DS.'re-encode_mp4.php force '.$hash." > /dev/null 2> /dev/null &");

        if(defined('ALT_FOLDER') && ALT_FOLDER)
        {
            $altname=ALT_FOLDER.DS.$hash;
            if(!file_exists($altname) && is_dir(ALT_FOLDER))
            {
                copy($file,$altname);
            }
        }

        if(defined('LOG_UPLOADER') && LOG_UPLOADER)
		{
			$fh = fopen(ROOT.DS.'data'.DS.'uploads.txt', 'a');
			fwrite($fh, time().';'.$url.';'.$hash.';'.getUserIP()."\n");
			fclose($fh);
		}
        
        return array('status'=>'ok','hash'=>$hash,'url'=>URL.$hash);
    }


    //via gist: https://gist.github.com/codler/3906826
    function serveMP4($path,$hash)
    {
        if ($fp = fopen($path, "rb"))
        {
            $size = filesize($path); 
            $length = $size;
            $start = 0;  
            $end = $size - 1; 
            header('Content-type: video/mp4');
            header("Accept-Ranges: 0-$length");
            if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
            }
            header("Content-Range: bytes $start-$end/$size");
            header("Content-Length: ".$length);
            $buffer = 1024 * 8;
            while(!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                $buffer = $end - $p + 1;
            }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
            }
            fclose($fp);
            exit();
        } else die('file not found');
    }

    function isProperMP4($filename)
	{
		$file = escapeshellarg($filename);
		$tmp = ROOT.DS.'tmp'.DS.md5(time()+rand(1,10000)).'.'.rand(1,10000).'.log';
        $bin = escapeshellcmd(FFMPEG_BINARY);
        
        
		
		$cmd = "$bin -i $file > $tmp 2>> $tmp";

        system($cmd);
        
        //var_dump(system( "$bin -i $file "));

		$answer = file($tmp);
		unlink($tmp);
		$ismp4 = false;
		if(is_array($answer))
		foreach($answer as $line)
		{
			$line = trim($line);
			if(strpos($line,'Duration: 00:00:00')) return false;
			if(strpos($line, 'Video: h264'))
				$ismp4 = true;
		}

		return $ismp4;
	}

    function rightEncodedMP4($file)
    {
        $hash = md5($file);
        $cmd = FFMPEG_BINARY." -i $file -hide_banner 2> ".ROOT.DS.'tmp'.DS.$hash.'.txt';
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
}