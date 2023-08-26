<?php

class PlaceholderGenerator {

    function generateImage($modifiers)
    {
        $size = ($modifiers['size']?:'800x600');

        $sd = sizeStringToWidthHeight($size);
		$width  = $sd['width'];
        $height = $sd['height'];

        $im = imagecreatetruecolor($width, $height);

        return $im;
    }

    function addSizeText($im,$modifiers)
    {
        $size = imagesx($im).'x'.imagesy($im);
        $text = $size;
        //add the size as text in the center of the image
        $textcolor = imagecolorallocate($im, 0, 0, 0);
        $font = dirname(__FILE__).DS.'fonts/RonysiswadiArchitect5-1GErv.ttf';
        //calculate the size of the text to make sure it will alway be visible
        $fontsize = 20;
        $textsize = imagettfbbox($fontsize, 0, $font, $text);
        $scaleX = imagesx($im) / ($textsize[2] - $textsize[0] + 25);
        $scaleY = imagesy($im) / ($textsize[1] - $textsize[7] + 25);

        $scale = min($scaleX,$scaleY);
        
        $fontsize = 20 * $scale;
        $textsize = imagettfbbox($fontsize, 0, $font, $text);
        $textwidth = $textsize[2] - $textsize[0];
        $textheight = $textsize[1] - $textsize[7];

        if($textwidth > imagesx($im) || $textheight > imagesy($im))
            return $im;

        $x = (imagesx($im) - $textwidth) / 2;
        $y = (imagesy($im) - $textheight) / 2 + $textheight;
        imagettftext($im, $fontsize, 0, $x, $y, $textcolor, $font, $text);

        return $im;
    }

    function gradient($im, $c) {

        $w = imagesx($im);
        $h = imagesy($im);

        if(!$c[0]) $c = ['ffffff','ffffff','ffffff','ffffff'];
        else if(!$c[1]) $c = [$c[0],$c[0],$c[0],$c[0]];
        else if(!$c[2]) $c = [$c[0],$c[0],$c[1],$c[1]];
        else if(!$c[3]) $c = [$c[0],$c[1],$c[2],$c[0]];

        for($i=0;$i<=3;$i++) {
            $c[$i]=$this->hex2rgb($c[$i]);
        }

        $rgb=$c[0]; // start with top left color
        for($x=0;$x<=$w;$x++) { // loop columns
            for($y=0;$y<=$h;$y++) { // loop rows
                // set pixel color 
                $col=imagecolorallocate($im,$rgb[0],$rgb[1],$rgb[2]);
                imagesetpixel($im,$x-1,$y-1,$col);
                // calculate new color  
                for($i=0;$i<=2;$i++) {
                    $rgb[$i]=
                        $c[0][$i]*(($w-$x)*($h-$y)/($w*$h)) +
                        $c[1][$i]*($x     *($h-$y)/($w*$h)) +
                        $c[2][$i]*(($w-$x)*$y     /($w*$h)) +
                        $c[3][$i]*($x     *$y     /($w*$h));
                }
            }
        }
        return $im;
    }

    function hex2rgb($hex)
    {
        $rgb[0]=hexdec(substr($hex,0,2));
        $rgb[1]=hexdec(substr($hex,2,2));
        $rgb[2]=hexdec(substr($hex,4,2));
        return($rgb);
    }
}