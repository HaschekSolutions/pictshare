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
			case 'x-png': 	return 'png';
			case 'png':		return 'png';
			case 'jpeg':	return 'jpg';
			case 'pjpeg':	return 'jpg';
			case 'gif':		return 'gif';
			default: return false;
		}
	}

	function uploadImageFromURL($url)
	{
		$type = $this->getTypeOfFile($url);
		$type = $this->isTypeAllowed($type);
		if(!$type)
			return array('status'=>'ERR','reason'=>'wrong filetype');
		$hash = $this->getNewHash($type);
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

		return array('status'=>'OK','type'=>$type,'hash'=>$hash,'url'=>DOMAINPATH.$hash);
	}

	function ProcessUploads()
	{
		$im = new Image();
		$i = 0;
		foreach ($_FILES["pic"]["error"] as $key => $error)
		{
			if ($error == UPLOAD_ERR_OK)
			{
				$dup_id = $this->isDuplicate($_FILES["pic"]["tmp_name"][$key]);
				if(!$dup_id)
				{
					$data = $this->uploadImageFromURL($_FILES["pic"]["tmp_name"][$key],false);
					if($data['hash'])
						$this->saveSHAOfFile($_FILES["pic"]["tmp_name"][$key],$data['hash']);
				}
				else
					$data = array('hash'=>$dup_id,'status'=>'OK');

				if($data['status']=='OK')
				{
					$o.= '<h2>'.$this->translate(4).' '.++$i.'</h2><a target="_blank" href="'.DOMAINPATH.$data['hash'].'"><img src="'.DOMAINPATH.'300/'.$data['hash'].'" /></a><br/>';
				}
			}
		}
		$html = new HTML();
		//if($i==1)$html->goToLocation('/i/info/'.$data['hash']);
		
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

	function GetPictureDimensions($bildname)
	{
		$size = getimagesize($bildname);  
		return $size;
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


	function AddWatermark($bildname,$type)
	{
		$watermark = imagecreatefrompng('inc/wasserzeichen.png');
				$watermark_width = imagesx($watermark);  
				$watermark_height = imagesy($watermark);
				
				if($type=='image/png' ||$type == 'png')
					$image = imagecreatefrompng($bildname);
				else if($type=='image/jpeg' ||$type == 'jpg')
					$image = imagecreatefromjpeg($bildname);
				else if($type=='image/gif' ||$type == 'gif')
					$image = imagecreatefromgif($bildname);
				else exit("Dateityp nicht erkannt");
				
				$size = getimagesize($bildname);  
				$dest_x = $size[0] - $watermark_width - 5;  
				$dest_y = $size[1] - $watermark_height - 5;
				
				imagesavealpha($watermark,true);
				
				//imagecopymerge($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, 100);
				imagecopy($image, $watermark,  $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height); 
				
				//$black = imagecolorallocate($image, 0, 0, 0);
				//imagecolortransparent($image, $black);
			
			imagesavealpha($image,true);
				
			if($type=='image/png'|| $type == 'png')
				imagepng($image,$bildname,5);
			else if($type=='image/jpeg'|| $type == 'jpg')
				imagejpeg($image,$bildname,100);
			else if($type=='image/gif'|| $type == 'gif')
				imagegif($image,$bildname);
	}

	function BildResize($filename,$max_hoehe,$max_breite,$output)
	{
		$image = new Imagick($filename);
		$image->adaptiveResizeImage($max_hoehe,$max_breite);
		$im->imageWriteFile (fopen ($output, "wb"));

		return $output;
		///////////////////////////

		// Set a maximum height and width
		$width = $max_breite;
		$height = $max_hoehe;
		
		// Get new dimensions
		list($width_orig, $height_orig) = getimagesize($filename);
		$size = getimagesize($filename);
		$ratio_orig = $width_orig/$height_orig;
		if($width_orig < $width && $height_orig < $height)
		{
			$width = $width_orig;
			$height = $height_orig;
		}
		else
		{
			if ($width/$height > $ratio_orig)
			   $width = $height*$ratio_orig;
			else
			   $height = $width/$ratio_orig;
		}

		// Resample
		$image_p = imagecreatetruecolor($width, $height);
		
			 if($size['mime']=='image/jpeg')
				$image = imagecreatefromjpeg($filename);
		else if($size['mime']=='image/png')
				$image = imagecreatefrompng($filename);
		else if($size['mime']=='image/gif')
				$image = imagecreatefromgif($filename);
		else if($size['mime']=='image/bmp')
				$image = imagecreatefromwbmp($filename);
		else exit('Bildart nicht unterstützt');
		
		imagesavealpha($image,true);
		
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

		// Output
		$file = $output;

		imagesavealpha($image_p,true);
		
		if($size['mime']=='image/jpeg')
				imagejpeg($image_p, $file, 100);
		else if($size['mime']=='image/png')
				imagepng($image_p, $file, 5);
		else if($size['mime']=='image/gif')
				imagegif($image_p, $file);
		//else if($size['mime']=='image/bmp')
				//$image = imagecreatefromwbmp($filename);
		else exit('Bildart nicht unterstützt');
		
		
		return $file;
	}

	function getUniqueIPs($hash,$type)
	{
		$db = new SQLite3("pictures.db");
		$results = $db->query("SELECT DISTINCT(ip) FROM views WHERE hash = '$hash' OR hash = '$hash.$type'");
		$i = 0;
        while ($row = $results->fetchArray())
        {
            $i++;
        }

        return $i;
	}

	function renderGraphOfImage($hash,$type)
	{
		$db = new SQLite3("pictures.db");
        $results = $db->query("SELECT * FROM views WHERE hash = '$hash' OR hash = '$hash.$type' ORDER BY time ASC");
        $first = false;
        $count = 0;
        while ($row = $results->fetchArray())
        {
        	$count++;
        	$index = floor(($row['time']+7200)/3600);
        	if(!$first)
        		$first = $index;
            $data[$index]++;
            $ref = $row['referrer'];
            if($ref=='-')
            	$a[2] = $this->translate(14);
            else
            	$a = explode('/', $ref);
            $domains[$a[2]]++;
        }

        if(!$count) return;

        $lasttime = $first*3600;

        foreach ($domains as $dom => $count)
        {
        	$doms[] = "['$dom',   $count]";
        }
        $doms = implode(',', $doms);

        foreach($data as $time=>$count)
        {
        	$time *=3600;
        	$difh = (($time-$lasttime)/3600)-1;
        	if($difh>0)
        		for($i=0;$i<$difh;$i++)
        			$d[] = '['.(($lasttime+(($i+1)*3600))*1000).', 0]';
        	$d[] = '['.($time*1000).', '.$count.']';
        	$lasttime = $time;
        }
        array_pop($d);
        $d = implode(',', $d);

		$o = "$(function () {
		        $('#container').highcharts({
		            chart: {
		            	zoomType: 'x',
		                type: 'spline'
		            },
		            title: {
		                text: 'Views of this image compared to time'
		            },
		            xAxis: {
		                type: 'datetime',
		                maxZoom: 3600000,
		                title: {
		                    text: null
		                }

		            },
		            yAxis: {
		                title: {
		                    text: 'Views / Hour'
		                },
		                min: 0
		            },
		            tooltip: {
		            	crosshairs: true,

		                pointFormat: '<span style=\"color:{series.color}\">{series.name}</span>: <b>{point.y}</b><br/>',

		            },
            plotOptions: {
                spline: {
                    lineWidth: 4,
                    states: {
                        hover: {
                            lineWidth: 5
                        }
                    },
                    marker: {
                        enabled: false
                    }
                }
            },
		            legend: {
		                enabled: true
		            },
		            series: [{
		                name: 'Views',
		                data: [$d]
		            }]
		        });
		    });


			$(function () {
			    var chart;
			    
			    $(document).ready(function () {
			    	
			    	// Build the chart
			        $('#pie').highcharts({
			            chart: {
			                plotBackgroundColor: null,
			                plotBorderWidth: null,
			                plotShadow: false
			            },
			            title: {
			                text: 'Referring sites'
			            },
			            tooltip: {
			        	    pointFormat: '{series.name}: <b>{point.y} = {point.percentage:.2f}%</b>'
			            },
			            plotOptions: {
			                pie: {
			                    allowPointSelect: true,
			                    cursor: 'pointer',
			                    dataLabels: {
			                        enabled: false
			                    },
			                    showInLegend: true
			                }
			            },
			            series: [{
			                type: 'pie',
			                name: '".$this->translate(15)."',
			                data: [
			                    $doms
			                ]
			            }]
			        });
			    });
			    
			});";

		return $o;
	}
}
