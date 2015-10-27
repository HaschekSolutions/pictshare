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
}