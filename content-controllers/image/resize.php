<?php

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
        $sd = sizeStringToWidthHeight($size);
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
        $sd = sizeStringToWidthHeight($size);
		$maxwidth  = $sd['width'];
        $maxheight = $sd['height'];
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        if(defined('ALLOW_BLOATING') && !ALLOW_BLOATING)
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