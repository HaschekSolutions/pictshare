<?php

class PictshareModel extends Model
{
	function backend($params)
	{
		switch($params[0])
		{
			case 'mp4convert':
				$hash = $params[1];
				$path = $params[2];
				$source = $path.$hash;
				if(!$this->isImage($hash))
					exit('[x] Hash not found'."\n");
				echo "[i] Converting $hash to mp4\n";
				$this->saveAsMP4($source,$path.'mp4_1.'.$hash);
				$this->saveAsMP4($source,$path.'ogg_1.'.$hash);
			break;
		}
		
		return array('status'=>'ok');
	}
	
	function getURLInfo($url,$ispath=false)
	{
		$url = rawurldecode($url);
		$data = $this->urlToData($url);	
		$hash = $data['hash'];
		if(!$hash)
			return array('status'=>'ERR','Reason'=>'Image not found');
		
		$file = $this->getCacheName($data);
		$html = new HTML;
		
		$path = ROOT.DS.'upload'.DS.$hash.DS.$file;
		if(!file_exists($path))
			$path = ROOT.DS.'upload'.DS.$hash.DS.$hash;
		if(file_exists($path))
		{
			$type = $this->getType($path);
			if($ispath)
				$byte = filesize($url);
			else $byte = filesize($path);

			
			if($type=='mp4')
			{
				$info = $this->getSizeOfMP4($path);
				$width = intval($info['width']);
    			$height = intval($info['height']);
			}
			else
			{
				list($width, $height) = getimagesize($path);
			}
			return array('status'=>'ok','hash'=>$hash,'cachename'=>$file,'size'=>$byte,'humansize'=>$html->renderSize($byte),'width'=>$width,'height'=>$height,'type'=>$type);
		}
			
		else
			return array('status'=>'ERR','Reason'=>'Image not found');
	}

	function couldThisBeAnImage($string)
	{
		$len = strlen($string);
		$dot = strpos($string,'.');
		if(!$dot) return false;
		if($dot<=10 && ( ($len-$dot) == 4 || ($len-$dot) == 5 ) )
			return true;
	}
	
	function urlToData($url)
	{
		$html = new HTML;
		$url = explode("/",$url);
		foreach($url as $el)
		{
			$el = $html->sanatizeString($el);
			$el = strtolower($el);
			if(!$el) continue;

			if(IMAGE_CHANGE_CODE && substr($el,0,10)=='changecode')
				$data['changecode'] = substr($el,11);
			
			if($this->isImage($el))
			{
				//if there are more than one hashes in url
				//make an album from them
				if($data['hash'])
				{
					if(!$data['album'])
						$data['album'][] = $data['hash'];
					$data['album'][] = $el;
				}
				$data['hash']=$el;
			}
			else if(BACKBLAZE === true && $this->couldThisBeAnImage($el) && BACKBLAZE_AUTODOWNLOAD ===true) //looks like it might be a hash but didn't find it here. Let's see
			{
				$b = new Backblaze();
				if($b->download($el)) //if the backblaze download function says it's an image, we'll take it
					$data['hash'] = $el;
			}
			else if($el=='mp4' || $el=='raw' || $el=='preview' || $el=='webm' || $el=='ogg')
				$data[$el] = 1;
			else if($this->isSize($el))
				$data['size'] = $el;
			else if($el=='embed')
				$data['embed'] = true;
			else if($el=='responsive')
				$data['responsive'] = true;
			else if($this->isRotation($el))
				$data['rotate'] = $el;
			else if($this->isFilter($el))
				$data['filter'][] = $el;
			else if($legacy = $this->isLegacyThumbnail($el)) //so old uploads will still work
			{
				$data['hash'] = $legacy['hash'];
				$data['size'] = $legacy['size'];
			}
			else if($el=='forcesize')
				$data['forcesize'] = true;
			else if(strlen(MASTER_DELETE_CODE)>10 && $el=='delete_'.strtolower(MASTER_DELETE_CODE))
				$data['delete'] = true;
			else if($el=='delete' && $this->mayDeleteImages()===true)
				$data['delete'] = true;
			else if((strlen(MASTER_DELETE_CODE)>10 && $el=='delete_'.strtolower(MASTER_DELETE_CODE)) || $this->deleteCodeExists($el))
				$data['delete'] = $this->deleteCodeExists($el)?$el:true;
				
		}

		if($data['delete'] && $data['hash'])
		{
			if($data['delete']===true || $this->isThisDeleteCodeForImage($data['delete'],$data['hash'],true))
				$this->deleteImage($data['hash']);
			return false;
		}
			
		if($data['mp4'])
		{
			$hash = $data['hash'];
			if(!$hash || $this->getTypeOfHash($hash)!='gif')
				unset($data['mp4']);
		}
		
		return $data;
	}

	function mayDeleteImages()
	{
		if(!defined('MASTER_DELETE_IP') || !MASTER_DELETE_IP) return false;
		$ip = getUserIP();
		$parts = explode(';',MASTER_DELETE_IP);
		foreach($parts as $part)
		{
			if(strpos($part,'/')!==false) 		//it's a CIDR address
			{
				if(cidr_match($ip, $part))
					return true;
			}
			else if(isIP($part)) 		  		//it's an IP address
			{
				if($part==$ip) return true;
			}
			else if(gethostbyname($part)==$ip)	//must be a hostname
			{
				return true;
			}

		}

		return false;
	}
	
	function deleteImage($hash)
    {
		//delete hash from hashes.csv
		$tmpname = ROOT.DS.'upload'.DS.'delete_temp.csv';
		$csv = ROOT.DS.'upload'.DS.'hashes.csv';
		$fptemp = fopen($tmpname, "w");
		if (($handle = fopen($csv, "r")) !== FALSE)
		{
			while (($line = fgets($handle)) !== false)
			{
				$data = explode(';',$line);
				if ($hash != trim($data[1]) )
				{
					fwrite($fptemp, $line);
				}
			}
		}
		fclose($handle);
		fclose($fptemp);
		unlink($csv);
		rename($tmpname, $csv);
		//unlink($tmpname);

		//delete actual image
        $base_path = ROOT.DS.'upload'.DS.$hash.DS;
		if(!is_dir($base_path)) return false;
		if ($handle = opendir($base_path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if ($entry != "." && $entry != "..")
				{
					unlink($base_path.$entry);
				}
			}
			closedir($handle);
		}
		
		rmdir($base_path);

		//delete from backblaze if configured
		if(BACKBLAZE===true && BACKBLAZE_AUTODELETE===true)
		{
			$b = new Backblaze();
			$b->deleteFile($hash);
		}
		
		
		return true;
    }
	
	function isLegacyThumbnail($val)
	{
		if(strpos($val,'_'))
		{
			$a = explode('_',$val);
			$size = $a[0];
			$hash = $a[1];
			if(!$this->isSize($size) || !$this->isImage($hash)) return false;
			
			return array('hash'=>$hash,'size'=>$size);
		}
		else return false;
	}
	
	function isFilter($var)
	{
		if(strpos($var,'_'))
		{
			$a = explode('_',$var);
			$var = $a[0];
			$val = $a[1];
			if(!is_numeric($val)) return false;
		}
		
		switch($var)
		{
			case 'negative':
			case 'grayscale': 
			case 'brightness': 
			case 'edgedetect': 
			case 'smooth': 
			case 'contrast':
			case 'blur':
			case 'sepia':
			case 'sharpen':
			case 'emboss':
			case 'cool':
			case 'light':
			case 'aqua':
			case 'fuzzy':
			case 'boost':
			case 'gray':
			case 'pixelate': return true; 
			
			default: return false;
		}
	}
	
	function isRotation($var)
	{
		switch($var)
		{
			case 'upside':
			case 'left':
			case 'right': return true;
			
			default: return false;
		}
	}
	
	function renderLegacyResized($path)
	{
		$a = explode('_',$path);
		if(count($a)!=2) return false;
		
		$hash = $a[1];
		$size = $a[0];
		
		if(!$this->hashExists($hash)) return false;
		
		
		
		renderResizedImage($size,$hash);
	}
	
	function getCacheName($data)
	{
		ksort($data);
		unset($data['raw']);
		//unset($data['preview']);
		$name = false;
		foreach($data as $key=>$val)
		{
			if($key!='hash')
			{
				if(!is_array($val))
					$name[] = $key.'_'.$val;
				else 
					foreach($val as $valdata)
						$name[] = $valdata;
			}
		}
		
		if(is_array($name))
			$name = implode('.',$name);
		
		return ($name?$name.'.':'').$data['hash'];
	}
	
	function isSize($var)
	{
		if(is_numeric($var)) return true;
		$a = explode('x',$var);
		if(count($a)!=2 || !is_numeric($a[0]) || !is_numeric($a[1])) return false;
		
		return true;
	}
	
	function isImage($hash)
	{
		if(!$hash) return false;
		return $this->hashExists($hash);
	}

	function renderUploadForm()
	{
		$maxfilesize = (int)(ini_get('upload_max_filesize'));
		
		if(UPLOAD_CODE)
			$upload_code_form = '<strong>'.$this->translate(20).': </strong><input class="input" type="password" name="upload_code" value="'.$_REQUEST['upload_code'].'"><div class="clear"></div>';
		
		return '
		<div class="clear"></div>
		<strong>'.$this->translate(0).': '.$maxfilesize.'MB / File</strong><br>
		<strong>'.$this->translate(1).'</strong>
		<br><br>
		<FORM id="form" enctype="multipart/form-data" method="post">
		<div id="formular">
			'.$upload_code_form.'
			<strong>'.$this->translate(4).': </strong><input class="input" type="file" name="pic[]" multiple><div class="clear"></div>
			<div class="clear"></div><br>
		</div>
			<INPUT class="btn" style="font-size:15px;font-weight:bold;background-color:#74BDDE;padding:3px;" type="submit" id="submit" name="submit" value="'.$this->translate(3).'" onClick="setTimeout(function(){document.getElementById(\'submit\').disabled = \'disabled\';}, 1);$(\'#movingBallG\').fadeIn()">
			<div id="movingBallG" class="invisible">
				<div class="movingBallLineG"></div>
				<div id="movingBallG_1" class="movingBallG"></div>
			</div>
		</FORM>';
	}

	function getNewHash($type,$length=10)
	{
		while(1)
		{
			$hash = getRandomString($length).'.'.$type;
			if(!$this->hashExists($hash)) return $hash;
		}
	}
	
	function hashExists($hash)
	{
		return is_dir(ROOT.DS.'upload'.DS.$hash);
	}
	
	function countResizedImages($hash)
	{
		$fi = new FilesystemIterator(ROOT.DS.'upload'.DS.$hash.DS, FilesystemIterator::SKIP_DOTS);
		return iterator_count($fi);
	}

	function getTypeOfFile($url)
	{
		$fi = new finfo(FILEINFO_MIME);
		$type = $fi->buffer(file_get_contents($url, false, null, -1, 1024));

		//to catch a strange error for PHP7 and Alpine Linux
		//if the file seems to be a stream, use unix file command
		if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && startsWith($type,'application/octet-stream'))
		{
			$content_type = exec("file -bi " . escapeshellarg($url));
			if($content_type && $content_type!=$type && strpos($content_type,'/')!==false && strpos($content_type,';')!==false)
				$type = $content_type;
		}

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

		if($type=='octet-stream' && $this->isProperMP4($url)) return 'mp4';
		if($type=='mp4' && !$this->isProperMP4($url))
			return false;
		

		return $type;

	}

	function isTypeAllowed($type)
	{
		switch($type)
		{
			case 'image/png':	return 'png';
			case 'image/x-png':	return 'png';
			case 'x-png':		return 'png';
			case 'png':			return 'png';
			
			case 'image/jpeg':	return 'jpg';
			case 'jpeg':		return 'jpg';
			case 'pjpeg':		return 'jpg';
			
			case 'image/gif':	return 'gif';
			case 'gif':			return 'gif';

			case 'mp4':			return 'mp4';

			default: return false;
		}
	}

	function getType($url)
	{
		return $this->isTypeAllowed($this->getTypeOfFile($url));
	}

	function uploadImageFromURL($url)
	{
		$type = $this->getTypeOfFile($url);
		$type = $this->isTypeAllowed($type);
		
		if(!$type)
			return array('status'=>'ERR','reason'=>'wrong filetype');

		$dup_id = $this->isDuplicate($url);
		if($dup_id)
		{
			$hash = $dup_id;
			$url = ROOT.DS.'upload'.DS.$hash.DS.$hash;
		}
		else
		{
			$hash = $this->getNewHash($type);
			$this->saveSHAOfFile($url,$hash);
		}
		
		
		
			
		if($dup_id)
			return array('status'=>'OK','type'=>$type,'hash'=>$hash,'url'=>DOMAINPATH.PATH.$hash,'domain'=>DOMAINPATH);
		
		mkdir(ROOT.DS.'upload'.DS.$hash);
		$file = ROOT.DS.'upload'.DS.$hash.DS.$hash;
		
		file_put_contents($file, file_get_contents($url));

		//remove all exif data from jpeg
		if($type=='jpg')
		{
			$res = imagecreatefromjpeg($file);
			imagejpeg($res, $file, (defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
		}

		//re-render new mp4 by calling the re-encode script
		if($type=='mp4' && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			system("nohup php ".ROOT.DS.'tools'.DS.'re-encode_mp4.php force '.$hash." > /dev/null 2> /dev/null &");
		}

		if(LOG_UPLOADER)
		{
			$fh = fopen(ROOT.DS.'upload'.DS.'uploads.txt', 'a');
			fwrite($fh, time().';'.$url.';'.$hash.';'.getUserIP()."\n");
			fclose($fh);
		}

		if(BACKBLAZE===true && BACKBLAZE_AUTOUPLOAD===true)
		{
			$b = new Backblaze();
			$b->upload($hash);
		}

		return array('status'=>'OK','type'=>$type,'hash'=>$hash,'url'=>DOMAINPATH.PATH.$hash,'domain'=>DOMAINPATH,'deletecode'=>$this->generateDeleteCodeForImage($hash));
	}

	function generateDeleteCodeForImage($hash)
	{
		while(1)
		{
			$code = getRandomString(32);
			$file = ROOT.DS.'upload'.DS.'deletecodes'.DS.$code;
			if(file_exists($file)) continue;
			file_put_contents($file,$hash);
			return $code;
		}
	}

	function deleteCodeExists($code)
	{
		if(strpos($code,'_')) $code = substr($code,strpos($code,'_')+1);
		if(!$code || !ctype_alnum($code)) return false;
		$file = ROOT.DS.'upload'.DS.'deletecodes'.DS.$code;
		return file_exists($file);
	}

	function isThisDeleteCodeForImage($code,$hash,$deleteiftrue=false)
	{
		if(strpos($code,'_')) $code = substr($code,strpos($code,'_')+1);
		if(!ctype_alnum($code) || !$hash) return false;
		$file = ROOT.DS.'upload'.DS.'deletecodes'.DS.$code;
		if(!file_exists($file)) return false;
		$rhash = trim(file_get_contents($file));

		$result = ($rhash==$hash)?true:false;

		if($deleteiftrue===true && $result===true)
			unlink($file);
		
		return $result;
	}
	
	function uploadCodeExists($code)
	{
		if(strpos(UPLOAD_CODE,';'))
		{
			$codes = explode(';',UPLOAD_CODE);
			foreach($codes as $ucode)
				if($code==$ucode) return true;
		}	
		
		if($code==UPLOAD_CODE) return true;
		
		return false;
	}
	
	function changeCodeExists($code)
	{
		if(!IMAGE_CHANGE_CODE) return true;
		if(strpos(IMAGE_CHANGE_CODE,';'))
		{
			$codes = explode(';',IMAGE_CHANGE_CODE);
			foreach($codes as $ucode)
				if($code==$ucode) return true;
		}
		
		if($code==IMAGE_CHANGE_CODE) return true;
		
		return false;
	}
	
	function processSingleUpload($file,$name)
	{
		if(UPLOAD_CODE && !$this->uploadCodeExists($_REQUEST['upload_code']))
			exit(json_encode(array('status'=>'ERR','reason'=>$this->translate(21))));
		
		$im = new Image();
		if ($_FILES[$name]["error"] == UPLOAD_ERR_OK)
		{
			$type = $this->getTypeOfFile($_FILES[$name]["tmp_name"]);
			$type = $this->isTypeAllowed($type);
			if(!$type) exit(json_encode(array('status'=>'ERR','reason'=>'Unsupported type')));
			
			$data = $this->uploadImageFromURL($_FILES[$name]["tmp_name"]);		
			if($data['status']=='OK')
			{
				$hash = $data['hash'];
				$o = array('status'=>'OK','type'=>$type,'hash'=>$hash,'url'=>DOMAINPATH.'/'.$hash,'domain'=>DOMAINPATH);
				if($data['deletecode'])
					$o['deletecode'] = $data['deletecode'];

				return $o;
			}
		}

		
		return $o;
	}

	function ProcessUploads()
	{
		if($_POST['submit']!=$this->translate(3)) return false;
		
		if(UPLOAD_CODE && !$this->uploadCodeExists($_REQUEST['upload_code']))
			return '<span class="error">' . $this->translate(21) . '</span>';
		
		$im = new Image();
		$i = 0;
		foreach ($_FILES["pic"]["error"] as $key => $error)
		{
			if ($error == UPLOAD_ERR_OK)
			{
				$data = $this->uploadImageFromURL($_FILES["pic"]["tmp_name"][$key]);

				if($data['status']=='OK')
				{
					if($data['deletecode'])
						$deletecode = '<br/><a target="_blank" href="'.DOMAINPATH.PATH.$data['hash'].'/delete_'.$data['deletecode'].'">Delete image</a>';
					else $deletecode = '';
					if($data['type']=='mp4')
						$o.= '<div><h2>'.$this->translate(4).' '.++$i.'</h2><a target="_blank" href="'.DOMAINPATH.PATH.$data['hash'].'">'.$data['hash'].'</a>'.$deletecode.'</div>';
					else
						$o.= '<div><h2>'.$this->translate(4).' '.++$i.'</h2><a target="_blank" href="'.DOMAINPATH.PATH.$data['hash'].'"><img src="'.DOMAINPATH.PATH.'300/'.$data['hash'].'" /></a>'.$deletecode.'</div>';

					$hashes[] = $data['hash'];
				}
			}
		}

		if(count($hashes)>1)
		{
			$albumlink = DOMAINPATH.PATH.implode('/',$hashes);
			$o.='<hr/><h1>Album link</h1><a href="'.$albumlink.'" >'.$albumlink.'</a>';

			$iframe = '<iframe frameborder="0" width="100%" height="500" src="'.$albumlink.'/300x300/forcesize/embed" <p>iframes are not supported by your browser.</p> </iframe>';
			$o.='<hr/><h1>Embed code</h1><input style="border:1px solid black;" size="100" type="text" value="'.addslashes(htmlentities($iframe)).'" />';
		}
		
		return $o;
	}
	
	function saveSHAOfFile($filepath,$hash)
	{
		$sha_file = ROOT.DS.'upload'.DS.'hashes.csv';
		$sha = sha1_file($filepath);
		$fp = fopen($sha_file,'a');
		fwrite($fp,"$sha;$hash\n");
		fclose($fp);
	}
	
	function isDuplicate($file)
	{
		$sha_file = ROOT.DS.'upload'.DS.'hashes.csv';
		$sha = sha1_file($file);
		if(!file_exists($sha_file)) return false;
		$fp = fopen($sha_file,'r');
		while (($line = fgets($fp)) !== false)
		{
			$line = trim($line);
			if(!$line) contine;
			$sha_upload = substr($line,0,40);
			if($sha_upload==$sha) //when it's a duplicate return the hash of the original file
			{
				fclose($fp);
				return substr($line,41);
			}
				 
		}
	
		fclose($fp);
		
		return false;
	}

	function translate($index,$params="")
	{
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		//$lang = 'en';
		switch ($lang){
		    case "de":
		    	$words[0] = 'Maximale Dateigröße';
		    	$words[1] = 'Es können auch mehrere Bilder auf einmal ausgewählt werden!';
		    	$words[2] = 'einfach, gratis, genial';
		    	$words[3] = 'Foto hinaufladen';
		    	$words[4] = 'Bild';
		    	$words[5] = 'Die Datei '.$params[0].' kann nicht hinaufgeladen werden, da der Dateityp "'.$params[1].'" nicht unterstützt wird.';
		    	$words[6] = 'Fehler beim Upload von '.$params;
		    	$words[7] = 'Bild "'.$params.'"" wurde erfolgreich hochgeladen';
		    	$words[8] = 'Skaliert auf';
		    	$words[9] = 'Kleinansicht';
		    	$words[10] = 'für Verlinkungen und Miniaturvorschau in Foren';
		    	$words[11] = 'Allgemeiner Fehler';
		    	$words[12] = 'Fehler 404 - nicht gefunden';
		    	$words[13] = 'Fehler 403 - nicht erlaubt';
		    	$words[14] = 'Kein refferer';
		    	$words[15] = 'Verlinkte Seiten';
		    	$words[16] = 'Hinweis: Zugriffe über pictshare.net werden nicht gerechnet';
		    	$words[17] = 'Dieses Bild wurde '.$params[0].' mal von '.$params[1].' verschiedenen IPs gesehen und hat '.$params[2].' Traffic verursacht';
		    	$words[18] = 'Dieses Bild wurde von folgenden Ländern aufgerufen: ';
		    	$words[19] = $params[0].' Aufrufe aus '.$params[1];
				$words[20] = 'Upload-Code';
				$words[21] = 'Falscher Upload Code eingegeben. Upload abgebrochen';

		    break;

		    default:
		      	$words[0] = 'Max filesize';
		      	$words[1] = 'You can select multiple pictures at once!';
		      	$words[2] = 'easy, free, engenious';
		      	$words[3] = 'Upload';
		      	$words[4] = 'Picture';
		      	$words[5] = 'The file '.$params[0].' can\'t be uploaded since the filetype "'.$params[1].'" is not supported.';
		    	$words[6] = 'Error uploading '.$params;
		    	$words[7] = 'Picture "'.$params.'"" was uploaded successfully';
		    	$words[8] = 'Scaled to';
		    	$words[9] = 'Thumbnail';
		    	$words[10] = 'for pasting in Forums, etc..';
		    	$words[11] = 'Unspecified error';
		    	$words[12] = 'Error 404 - not found';
		    	$words[13] = 'Error 403 - not allowed';
		    	$words[14] = 'No referrer';
		    	$words[15] = 'Linked sites';
		    	$words[16] = 'Note: Views from pictshare.net will not be counted';
		    	$words[17] = 'Was seen '.$params[0].' times by '.$params[1].' unique IPs and produced '.$params[2].' traffic';
		    	$words[18] = 'This picture was seen from the following countries: ';
		    	$words[19] = $params[0].' views from '.$params[1];
				$words[20] = 'Upload code';
				$words[21] = 'Invalid upload code provided';
		}

		return $words[$index];
	}
	
	function uploadImageFromBase64($data,$type=false)
	{
	        $type = $this->base64_to_type($data);
	        if(!$type)
	                return array('status'=>'ERR','reason'=>'wrong filetype','type'=>$type);
	        $hash = $this->getNewHash($type);
	        $picname = $hash;
	        $file = ROOT.DS.'tmp'.DS.$hash;
	        $this->base64_to_image($data,$file,$type);
			
			return $this->uploadImageFromURL($file);
	}

	function base64_to_type($base64_string)
	{
		$data = explode(',', $base64_string);
		$data = $data[1];
	
		$data = str_replace(' ','+',$data);
		$data = base64_decode($data);
	
		$info = getimagesizefromstring($data);
		
		
	
		trigger_error("########## FILETYPE: ".$info['mime']);
	
	
		$f = finfo_open();
		$type = $this->isTypeAllowed(finfo_buffer($f, $data, FILEINFO_MIME_TYPE));
	
		return $type;
	}
	
	function base64_to_image($base64_string, $output_file,$type)
	{
		$data = explode(',', $base64_string);
		$data = $data[1];
	
		$data = str_replace(' ','+',$data);
	
		$data = base64_decode($data);
	
		$source = imagecreatefromstring($data);
		switch($type)
		{
		case 'jpg':
				imagejpeg($source,$output_file,(defined('JPEG_COMPRESSION')?JPEG_COMPRESSION:90));
				trigger_error("========= SAVING AS ".$type." TO ".$output_file);
		break;
	
		case 'png':
				imagefill($source, 0, 0, IMG_COLOR_TRANSPARENT);
				imagesavealpha($source,true);
				imagepng($source,$output_file,(defined('PNG_COMPRESSION')?PNG_COMPRESSION:6));
				trigger_error("========= SAVING AS ".$type." TO ".$output_file);
		break;
	
		case 'gif':
				imagegif($source,$output_file);
				trigger_error("========= SAVING AS ".$type." TO ".$output_file);
		break;
	
		default:
				imagefill($source, 0, 0, IMG_COLOR_TRANSPARENT);
				imagesavealpha($source,true);
				imagepng($source,$output_file,(defined('PNG_COMPRESSION')?PNG_COMPRESSION:6));
		break;
		}
	
		//$imageSave = imagejpeg($source,$output_file,100);
		imagedestroy($source);
	
		return $type;
	}

	function getTypeOfHash($hash)
	{
	    $base_path = ROOT.DS.'upload'.DS.$hash.DS;
	    $path = $base_path.$hash;
	    $type = $this->isTypeAllowed($this->getTypeOfFile($path));

	    return $type;
	}
	
	function isProperMP4($filename)
	{
		$file = escapeshellarg($filename);
		$tmp = ROOT.DS.'tmp'.DS.md5(time()+rand(1,10000)).'.'.rand(1,10000).'.log';
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		
		$cmd = "$bin -i $file > $tmp 2>> $tmp";

		system($cmd);

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
	
	function resizeFFMPEG($data,$cachepath,$type='mp4')
	{
		$file = ROOT.DS.'upload'.DS.$data['hash'].DS.$data['hash'];
		$file = escapeshellarg($file);
		$tmp = '/dev/null';
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		
		$size = $data['size'];
		
		if(!$size) return $file;
		
		$sd = $this->sizeStringToWidthHeight($size);
		$maxwidth  = $sd['width'];
        $maxheight = $sd['height'];
		
		switch($type)
		{
			case 'mp4': 
				$addition = '-c:v libx264 -profile:v baseline -level 3.0 -pix_fmt yuv420p';
			break;
		}
		
        $maxheight = 'trunc(ow/a/2)*2';
		
		$cmd = "$bin -i $file -y -vf scale=\"$maxwidth:$maxheight\" $addition -f $type $cachepath";

		system($cmd);
		
		return $cachepath;
	}

	function gifToMP4($gifpath,$target)
	{
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		$file = escapeshellarg($gifpath);
		
		if(!file_exists($target)) //simple caching.. have to think of something better
		{
			$cmd = "$bin -f gif -y -i $file -c:v libx264 -f mp4 $target";
			system($cmd);
		}
		

		return $target;
	}

	function saveAsMP4($source,$target)
	{
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		$source = escapeshellarg($source);
		$target = escapeshellarg($target);
		$h265 = "$bin -y -i $source -an -c:v libx264 -qp 0 -f mp4 $target";
		system($h265);
	}

	function saveAsOGG($source,$target)
	{
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		$source = escapeshellarg($source);
		$target = escapeshellarg($target);
		$h265 = "$bin -y -i $source -vcodec libtheora -acodec libvorbis -qp 0 -f ogg $target";
		system($h265);
	}
	
	function saveAsWebm($source,$target)
	{
		return false;
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		$source = escapeshellarg($source);
		$target = escapeshellarg($target);
		$webm = "$bin -y -i $source -vcodec libvpx -acodec libvorbis -aq 5 -ac 2 -qmax 25 -f webm $target";
		system($webm);
	}

	function saveFirstFrameOfMP4($path,$target)
	{
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
		$file = escapeshellarg($path);
		$cmd = "$bin -y -i $file -vframes 1 -f image2 $target";
		
		system($cmd);
	}

	//from https://stackoverflow.com/questions/4847752/how-to-get-video-duration-dimension-and-size-in-php
	function getSizeOfMP4($video)
	{
		$video = escapeshellarg($video);
		$bin = escapeshellcmd(ROOT.DS.'bin'.DS.'ffmpeg');
	    $command = $bin . ' -i ' . $video . ' -vstats 2>&1';  
	    $output = shell_exec($command);  

	    $regex_sizes = "/Video: ([^,]*), ([^,]*), ([0-9]{1,4})x([0-9]{1,4})/";
	    if (preg_match($regex_sizes, $output, $regs)) {
	        $codec = $regs [1] ? $regs [1] : null;
	        $width = $regs [3] ? $regs [3] : null;
	        $height = $regs [4] ? $regs [4] : null;
	     }

	    $regex_duration = "/Duration: ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2}).([0-9]{1,2})/";
	    if (preg_match($regex_duration, $output, $regs)) {
	        $hours = $regs [1] ? $regs [1] : null;
	        $mins = $regs [2] ? $regs [2] : null;
	        $secs = $regs [3] ? $regs [3] : null;
	        $ms = $regs [4] ? $regs [4] : null;
	    }

	    return array ('codec' => $codec,
	            'width' => $width,
	            'height' => $height,
	            'hours' => $hours,
	            'mins' => $mins,
	            'secs' => $secs,
	            'ms' => $ms
	    );

	}

	function oembed($url,$type)
	{
		$data = $this->getURLInfo($url);
		$rawurl = $url.'/raw';
		switch($type)
		{
			case 'json':
				header('Content-Type: application/json');
				return array(	"version"=> "1.0",
								"type"=> "video",
								"thumbnail_url"=>$url.'/preview',
								"thumbnail_width"=>$data['width'],
								"thumbnail_height"=>$data['height'],
								"width"=> $data['width'],
								"height"=> $data['height'],
								"title"=> "PictShare",
								"provider_name"=> "PictShare",
								"provider_url"=> DOMAINPATH,
								"html"=> '<video id="video" poster="'.$url.'/preview'.'" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline>
							                <source src="'.$rawurl.'" type="video/mp4">
            							  </video>');
			break;

			case 'xml':

			break;
		}
	}

	function sizeStringToWidthHeight($size)
	{
		if(!$size || !$this->isSize($size)) return false;
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

}
