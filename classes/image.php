<?php

class Image
{
    function rotate(&$im,$direction)
    {
        switch($direction)
        {
            case 'upside': $angle = 180;break;
            case 'left': $angle = 90;break;
            case 'right': $angle = -90;break;
            default: $angle = 0;break;
        }
        
        $im = imagerotate($im,$angle,0);
    }
    
    function forceResize(&$img,$size)
    {
        $pm = new PictshareModel();
        
        $sd = $pm->sizeStringToWidthHeight($size);
		$maxwidth  = $sd['width'];
        $maxheight = $sd['height'];
        
        
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        $maxwidth = ($maxwidth>$width?$width:$maxwidth);
        $maxheight = ($maxheight>$height?$height:$maxheight);

        
        $dst_img = imagecreatetruecolor($maxwidth, $maxheight);
        $src_img = $img;
        
        $palsize = ImageColorsTotal($img);
        for ($i = 0; $i < $palsize; $i++)
        { 
            $colors = ImageColorsForIndex($img, $i);   
            ImageColorAllocate($dst_img, $colors['red'], $colors['green'], $colors['blue']);
        }
        
        imagefill($dst_img, 0, 0, IMG_COLOR_TRANSPARENT);
        imagesavealpha($dst_img,true);
        imagealphablending($dst_img, true);
        
        $width_new = $height * $maxwidth / $maxheight;
        $height_new = $width * $maxheight / $maxwidth;
        //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
        if($width_new > $width){
            //cut point by height
            $h_point = (($height - $height_new) / 2);
            //copy image
            imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $maxwidth, $maxheight, $width, $height_new);
        }else{
            //cut point by width
            $w_point = (($width - $width_new) / 2);
            imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $maxwidth, $maxheight, $width_new, $height);
        }
        
        $img = $dst_img;
    }
    
    /**
    * From: https://stackoverflow.com/questions/4590441/php-thumbnail-image-resizing-with-proportions
    */
    function resize(&$img,$size)
    {
        $pm = new PictshareModel();
        
        $sd = $pm->sizeStringToWidthHeight($size);
		$maxwidth  = $sd['width'];
        $maxheight = $sd['height'];
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        if(!ALLOW_BLOATING)
        {
            if($maxwidth>$width)$maxwidth = $width;
            if($maxheight>$height)$maxheight = $height;
        }
            
        if ($height > $width) 
        {   
            $ratio = $maxheight / $height;  
            $newheight = $maxheight;
            $newwidth = $width * $ratio; 
        }
        else 
        {
            $ratio = $maxwidth / $width;   
            $newwidth = $maxwidth;  
            $newheight = $height * $ratio;   
        }
        
        $newimg = imagecreatetruecolor($newwidth,$newheight); 
        
        $palsize = ImageColorsTotal($img);
        for ($i = 0; $i < $palsize; $i++)
        { 
            $colors = ImageColorsForIndex($img, $i);   
            ImageColorAllocate($newimg, $colors['red'], $colors['green'], $colors['blue']);
        }
        
        imagefill($newimg, 0, 0, IMG_COLOR_TRANSPARENT);
        imagesavealpha($newimg,true);
        imagealphablending($newimg, true);
        
        imagecopyresampled($newimg, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        
        $img = $newimg;
    }
    
    /**
	* Strong Blur
	*
	* @param resource $gdImageResource 
	* @param int $blurFactor optional 
	*  This is the strength of the blur
	*  0 = no blur, 3 = default, anything over 5 is extremely blurred
	* @return GD image resource
	* @author Martijn Frazer, idea based on http://stackoverflow.com/a/20264482
	*/
	function blur(&$gdImageResource, $blurFactor = 3)
	{
		if(!$blurFactor)
			$blurFactor = 3;
		if($blurFactor>6)
			$blurFactor = 6;
		else if($blurFactor<0)
			$blurFactor = 0;
	  // blurFactor has to be an integer
	  $blurFactor = round($blurFactor);
	  
	  $originalWidth = imagesx($gdImageResource);
	  $originalHeight = imagesy($gdImageResource);
	
	  $smallestWidth = ceil($originalWidth * pow(0.5, $blurFactor));
	  $smallestHeight = ceil($originalHeight * pow(0.5, $blurFactor));
	
	  // for the first run, the previous image is the original input
	  $prevImage = $gdImageResource;
	  $prevWidth = $originalWidth;
	  $prevHeight = $originalHeight;
	
	  // scale way down and gradually scale back up, blurring all the way
	  for($i = 0; $i < $blurFactor; $i += 1)
	  {    
	    // determine dimensions of next image
	    $nextWidth = $smallestWidth * pow(2, $i);
	    $nextHeight = $smallestHeight * pow(2, $i);
	
	    // resize previous image to next size
	    $nextImage = imagecreatetruecolor($nextWidth, $nextHeight);
	    imagecopyresized($nextImage, $prevImage, 0, 0, 0, 0, 
	      $nextWidth, $nextHeight, $prevWidth, $prevHeight);
	
	    // apply blur filter
	    imagefilter($nextImage, IMG_FILTER_GAUSSIAN_BLUR);
	
	    // now the new image becomes the previous image for the next step
	    $prevImage = $nextImage;
	    $prevWidth = $nextWidth;
	      $prevHeight = $nextHeight;
	  }
	
	  // scale back to original size and blur one more time
	  imagecopyresized($gdImageResource, $nextImage, 
	    0, 0, 0, 0, $originalWidth, $originalHeight, $nextWidth, $nextHeight);
	  imagefilter($gdImageResource, IMG_FILTER_GAUSSIAN_BLUR);
	
	  // clean up
	  imagedestroy($prevImage);
	
	  // return result
	  return $gdImageResource;
	}
    
    function filter(&$im,$vars)
    {
        
        foreach($vars as $var)
        {
            if(strpos($var,'_'))
            {
                $a = explode('_',$var);
                $var = $a[0];
                $val = $a[1];
            }
            switch($var)
            {
                case 'negative': imagefilter($im,IMG_FILTER_NEGATE); break;
                case 'grayscale': imagefilter($im,IMG_FILTER_GRAYSCALE); break; 
                case 'brightness': imagefilter($im,IMG_FILTER_BRIGHTNESS,$val); break; 
                case 'edgedetect': imagefilter($im,IMG_FILTER_EDGEDETECT); break; 
                case 'smooth': imagefilter($im,IMG_FILTER_SMOOTH,$val); break; 
                case 'contrast': imagefilter($im,IMG_FILTER_CONTRAST,$val); break;
                case 'pixelate': imagefilter($im,IMG_FILTER_PIXELATE,$val); break;
                case 'blur': $this->blur($im,$val); break;
                case 'sepia': (new Filter($im))->sepia()->getImage();break;
                case 'sharpen':(new Filter($im))->sharpen()->getImage();break;
				case 'emboss':(new Filter($im))->emboss()->getImage();break;
				case 'cool':(new Filter($im))->cool()->getImage();break;
				case 'light':(new Filter($im))->light()->getImage();break;
				case 'aqua':(new Filter($im))->aqua()->getImage();break;
				case 'fuzzy':(new Filter($im))->fuzzy()->getImage();break;
				case 'boost':(new Filter($im))->boost()->getImage();break;
				case 'gray':(new Filter($im))->gray()->getImage();break;
            }
        }
    }
}