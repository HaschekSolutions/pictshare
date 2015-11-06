<?php

class Image
{
    function getImage($hash,$size=false)
    {
        $path = ROOT.DS.'upload'.DS.$hash.DS;
        $fullpath = ROOT.DS.'upload'.DS.$hash.DS.$hash;

        $pm = new PictshareModel();
        $type = $pm->isTypeAllowed($pm->getTypeOfFile($fullpath));
        
        if(!$type) return false;
        
        if(is_array($size))
        {
            $width = $size[0];
            $height = $size[1];
        }
        else if($size)
        {
            $width = $size;
            $height = $size;
        }
        
        if($width && $height && $type!='gif')
            $filename = $width.'x'.$height.'_'.$hash;
        else
            $filename = $hash;
        
        if(file_exists($path.$filename))
                return '/'.$filename;
        
        
        $thumb = new easyphpthumbnail;
        if($width==$height)
            $thumb -> Thumbsize = $width;
        else
        {
            $size = getimagesize($fullpath);
            if(($width/$height)==($size[0]/$size[1]))
            {
                $thumb -> Thumbwidth = $width;
                $thumb -> Thumbheight = $height;
            }
            else if($width>$height)
                $thumb -> Thumbsize = $height;
            else
                $thumb -> Thumbsize = $width;
            //$thumb -> Thumbwidth = $width;
            //$thumb -> Thumbheight = $height;
        }
        $thumb -> Inflate = false;
        $thumb -> Thumblocation = $path; 
        //$thumb -> Thumbsaveas = $type; 
        $thumb -> Thumbfilename = $filename; 
        $thumb -> Createthumb($fullpath,'file');
        
        //var_dump($thumb -> Thumblocation.$thumb -> Thumbfilename);
        
        return '/'.$filename;
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
    
    /**
    * From: https://stackoverflow.com/questions/4590441/php-thumbnail-image-resizing-with-proportions
    */
    function resize(&$img,$size)
    {
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
            }
        }
    }
}