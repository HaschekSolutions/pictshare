<?php

class PictshareModel extends Model
{
	function backend($params)
	{
		return array('status'=>'ok');
	}

	function renderUploadForm()
	{
		$maxfilesize = (int)(ini_get('upload_max_filesize'));
		return '
		<div class="clear"></div>
		<strong>'.$this->translate(0).': '.$maxfilesize.'MB / File</strong><br>
		<strong>'.$this->translate(1).'</strong>
		<br><br>
		<FORM enctype="multipart/form-data" method="post">
		<div id="formular">
			<strong>'.$this->translate(4).': </strong><input class="input" type="file" name="pic[]" multiple><div class="clear"></div>
			<div class="clear"></div><br>
		</div>
			<INPUT style="font-size:15px;font-weight:bold;background-color:#74BDDE;padding:3px;" type="submit" id="submit" name="submit" value="'.$this->translate(3).'">
		</FORM>';
	}

	function getNewHash($type,$length=10)
	{
		while(1)
		{
			$hash = substr(md5(time().$type.rand(1,1000000).microtime()),0,$length).'.'.$type;
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
		$type = $fi->buffer(file_get_contents($url));

		$arr = explode(';', trim($type));
		if(count($arr)>1)
		{
			$a2 = explode('/', $arr[0]);
			return $a2[1];
		}
		$a2 = explode('/', $type);
			return $a2[1];
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
			default: return false;
		}
	}

	function uploadImageFromURL($url)
	{
		
				
		$type = $this->getTypeOfFile($url);
		$type = $this->isTypeAllowed($type);
			
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
		
		
		if(!$type)
			return array('status'=>'ERR','reason'=>'wrong filetype');
			
		if($dup_id)
			return array('status'=>'OK','type'=>$type,'hash'=>$hash,'url'=>DOMAINPATH.$hash,'domain'=>DOMAINPATH);
		
		mkdir(ROOT.DS.'upload'.DS.$hash);
		$file = ROOT.DS.'upload'.DS.$hash.DS.$hash;
		$status = file_put_contents($file, file_get_contents($url));

		//remove all exif data from jpeg
		if($type=='jpg')
		{
			$res = imagecreatefromjpeg($file);
			imagejpeg($res, $file, 100);
		}

		if(LOG_UPLOADER)
		{
			$fh = fopen(ROOT.DS.'upload'.DS.'uploads.txt', 'a');
			fwrite($fh, time().';'.$url.';'.$hash.';'.$_SERVER['REMOTE_ADDR']."\n");
			fclose($fh);
		}

		return array('status'=>'OK','type'=>$type,'hash'=>$hash,'url'=>DOMAINPATH.$hash,'domain'=>DOMAINPATH);
	}

	function ProcessUploads()
	{
		$im = new Image();
		$i = 0;
		foreach ($_FILES["pic"]["error"] as $key => $error)
		{
			if ($error == UPLOAD_ERR_OK)
			{
				$data = $this->uploadImageFromURL($_FILES["pic"]["tmp_name"][$key]);

				if($data['status']=='OK')
				{
					$o.= '<h2>'.$this->translate(4).' '.++$i.'</h2><a target="_blank" href="'.DOMAINPATH.$data['hash'].'"><img src="'.DOMAINPATH.'300/'.$data['hash'].'" /></a><br/>';
				}
			}
		}
		$html = new HTML();
		
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
				imagejpeg($source,$output_file,100);
				trigger_error("========= SAVING AS ".$type." TO ".$output_file);
		break;
	
		case 'png':
				imagepng($source,$output_file,5);
				trigger_error("========= SAVING AS ".$type." TO ".$output_file);
		break;
	
		case 'gif':
				imagegif($source,$output_file);
				trigger_error("========= SAVING AS ".$type." TO ".$output_file);
		break;
	
		default:
				imagepng($source,$output_file,5);
		break;
		}
	
		//$imageSave = imagejpeg($source,$output_file,100);
		imagedestroy($source);
	
		return $type;
	}
}
