<?php 

class VideoController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [
        'video/mp4',
        'video/x-m4v',
    ];

    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){return array('mp4');}

    public function handleHash($hash,$url)
    {
        $path = getDataDir().DS.$hash.DS.$hash;

        //@todo: - resize by changing $path

        //check if video should be resized
        foreach($url as $u)
            if(isSize($u)==true)
                $size = $u;
        if($size)
        {
            $s = sizeStringToWidthHeight($size);
            $width = $s['width'];
            $newpath = getDataDir().DS.$hash.DS.$width.'_'.$hash;
            if(!file_exists($newpath))
                $this->resize($path,$newpath,$width);
            $path = $newpath;
        }
        
        
        if(in_array('raw',$url))
            $this->serveMP4($path,$hash);
        else if(in_array('preview',$url))
        {
            $preview = $path.'_preview.jpg';
            if(!file_exists($preview))
            {
                $this->saveFirstFrameOfMP4($path,$preview);
            }

            header ("Content-type: image/jpeg");
            header('X-Accel-Redirect: '.str_replace(getDataDir().DS,'',$preview));

        }
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
                header('X-Accel-Redirect: '.str_replace(getDataDir().DS,'',$path));
                exit;
            }
        }
        else
        {
            $data = array('url'=>implode('/',$url),'hash'=>$hash,'filesize'=>renderSize(filesize($path)));
            return renderTemplate('video.html.php',$data);
            
        }
    }

    public function handleUpload($tmpfile,$hash=false)
    {
        if($hash===false)
            $hash = getNewHash('mp4',6);
        else
        {
            $hash.='.mp4';
            if(isExistingHash($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        $file = storeFile($tmpfile,$hash,true);

        if(!$this->rightEncodedMP4($file))
            system("nohup php ".ROOT.DS.'tools'.DS.'re-encode_mp4.php force '.$hash." > /dev/null 2> /dev/null &");
        
        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }


    //via gist: https://gist.github.com/codler/3906826
    function serveMP4($path,$hash)
    {
        header('Content-type: video/mp4');
        header ("Last-Modified: ".gmdate('D, d M Y H:i:s ', filemtime($path)) . 'GMT');
        header ("ETag: ".sha1_file($path));
        header('Cache-control: public, max-age=31536000');
        header('X-Accel-Redirect: '.str_replace(getDataDir().DS,'',$path));
        exit();
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

    function saveFirstFrameOfMP4($path,$target)
	{
		$bin = escapeshellcmd(FFMPEG_BINARY);
		$file = escapeshellarg($path);
		$cmd = "$bin -y -i $file -vframes 1 -f image2 $target";
		
		system($cmd);
    }
    
    function resize($in,$out,$width)
	{
		$file = escapeshellarg($in);
		$tmp = '/dev/null';
		$bin = escapeshellcmd(FFMPEG_BINARY);
		
		$addition = '-c:v libx264 -profile:v baseline -level 3.0 -pix_fmt yuv420p';
        $height = 'trunc(ow/a/2)*2';
		
		$cmd = "$bin -i $file -y -vf scale=\"$width:$height\" $addition $out";
		system($cmd);
		
		return (file_exists($out) && filesize($out)>0);
	}
}