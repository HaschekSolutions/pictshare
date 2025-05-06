<?php 

function getFilters()
{
    return get_class_methods('Filter');
}

class Filter {
	public function sepia($im,$val) {
		
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		imagefilter($im, IMG_FILTER_COLORIZE, 100, 50, 0);
		
		return $im;
	}
	public function sepia2($im,$val) {
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -10);
		imagefilter($im, IMG_FILTER_CONTRAST, -20);
		imagefilter($im, IMG_FILTER_COLORIZE, 60, 30, -15);
		return $im;
	}
	public function sharpen($im,$val) {
		
		$gaussian = array(
				array(1.0, 1.0, 1.0),
				array(1.0, -7.0, 1.0),
				array(1.0, 1.0, 1.0)
		);
		imageconvolution($im, $gaussian, 1, 4);
		
		return $im;
	}
	public function emboss($im,$val) {
		
		$gaussian = array(
				array(-2.0, -1.0, 0.0),
				array(-1.0, 1.0, 1.0),
				array(0.0, 1.0, 2.0)
		);
		
		imageconvolution($im, $gaussian, 1, 5);
		
		return $im;
	}
	public function cool($im,$val) {
		
		imagefilter($im, IMG_FILTER_MEAN_REMOVAL);
		imagefilter($im, IMG_FILTER_CONTRAST, -50);
		
		return $im;
	}
	public function light($im,$val) {
		
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 10);
		imagefilter($im, IMG_FILTER_COLORIZE, 100, 50, 0, 10);
		
		return $im;
	}
	public function aqua($im,$val) {
		
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 70, 0, 30);
		
		return $im;
	}
	public function fuzzy($im,$val) {
		
		$gaussian = array(
				array(1.0, 1.0, 1.0),
				array(1.0, 1.0, 1.0),
				array(1.0, 1.0, 1.0)
		);
		imageconvolution($im, $gaussian, 9, 20);
		
		return $im;
	}
	public function boost($im,$val) {
		
		imagefilter($im, IMG_FILTER_CONTRAST, -35);
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 10);
		
		return $im;
	}
	public function boost2($im,$val) {
		imagefilter( $im, IMG_FILTER_CONTRAST, -35);
		imagefilter( $im, IMG_FILTER_COLORIZE, 25, 25, 25);
		return $im;
	}
	public function gray($im,$val) {
		
		imagefilter($im, IMG_FILTER_CONTRAST, -60);
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		
		return $im;
	}
	public function antique($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 0);
		imagefilter($im, IMG_FILTER_CONTRAST, -30);
		imagefilter($im, IMG_FILTER_COLORIZE, 75, 50, 25);
		return $im;
	}
	public function blackwhite($im,$val) {
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 10);
		imagefilter($im, IMG_FILTER_CONTRAST, -20);
		return $im;
	}
	public function vintage($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 10);
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		imagefilter($im, IMG_FILTER_COLORIZE, 40, 10, -15);
		return $im;
	}
	
	public function concentrate($im,$val) {
		imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
		imagefilter($im, IMG_FILTER_SMOOTH, -10);
		return $im;
	}
	
	public function hermajesty($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -10);
		imagefilter($im, IMG_FILTER_CONTRAST, -5);
		imagefilter($im, IMG_FILTER_COLORIZE, 80, 0, 60);
		return $im;
	}
	public function everglow($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -30);
		imagefilter($im, IMG_FILTER_CONTRAST, -5);
		imagefilter($im, IMG_FILTER_COLORIZE, 30, 30, 0);
		return $im;
	}
	public function freshblue($im,$val) {
		imagefilter($im, IMG_FILTER_CONTRAST, -5);
		imagefilter($im, IMG_FILTER_COLORIZE, 20, 0, 80, 60);
		return $im;
	}
	public function tender($im,$val) {
		imagefilter($im, IMG_FILTER_CONTRAST, 5);
		imagefilter($im, IMG_FILTER_COLORIZE, 80, 20, 40, 50);
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 40, 40, 100);
		imagefilter($im, IMG_FILTER_SELECTIVE_BLUR);
		return $im;
	}
	public function dream($im,$val) {
		imagefilter($im, IMG_FILTER_COLORIZE, 150, 0, 0, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 50, 0, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
		return $im;
	}
	public function frozen($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -15);
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 0, 100, 50);
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 0, 100, 50);
		imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
		return $im;
	}
	public function forest($im,$val) {
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 0, 150, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 0, 150, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_SMOOTH, 10);
		return $im;
	}
	public function rain($im,$val) {
		imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
		imagefilter($im, IMG_FILTER_MEAN_REMOVAL);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 80, 50, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_SMOOTH, 10);
		return $im;
	}
	public function orangepeel($im,$val) {
		imagefilter($im, IMG_FILTER_COLORIZE, 100, 20, -50, 20);
		imagefilter($im, IMG_FILTER_SMOOTH, 10);
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -10);
		imagefilter($im, IMG_FILTER_CONTRAST, 10);
		imagegammacorrect($im, 1, 1.2 );
		return $im;
	}
	public function darken($im,$val) {
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -50);
		return $im;
	}
	public function summer($im,$val) {
		imagefilter($im, IMG_FILTER_COLORIZE, 0, 150, 0, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_COLORIZE, 25, 50, 0, 50);
		imagefilter($im, IMG_FILTER_NEGATE);
		return $im;
	}
	public function retro($im,$val) {
		imagefilter($im, IMG_FILTER_GRAYSCALE);
		imagefilter($im, IMG_FILTER_COLORIZE, 100, 25, 25, 50);
		return $im;
	}
	public function country($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, -30);
		imagefilter($im, IMG_FILTER_COLORIZE, 50, 50, 50, 50);
		imagegammacorrect($im, 1, 0.3);
		return $im;
	}
	public function washed($im,$val) {
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 30);
		imagefilter($im, IMG_FILTER_NEGATE);
		imagefilter($im, IMG_FILTER_COLORIZE, -50, 0, 20, 50);
		imagefilter($im, IMG_FILTER_NEGATE );
		imagefilter($im, IMG_FILTER_BRIGHTNESS, 10);
		imagegammacorrect($im, 1, 1.2);
		return $im;
	}

	public function pixelate($im,$val) {
		if($val==null) $val = 10;
		imagefilter($im,IMG_FILTER_PIXELATE,$val);
		return $im;
	}

	public function blur($im,$blurFactor)
	{
		if(!$blurFactor)
			$blurFactor = 3;
		if($blurFactor>6)
			$blurFactor = 6;
		else if($blurFactor<0)
			$blurFactor = 0;
	  // blurFactor has to be an integer
	  $blurFactor = round($blurFactor);
	  
	  $originalWidth = imagesx($im);
	  $originalHeight = imagesy($im);
	
	  $smallestWidth = ceil($originalWidth * pow(0.5, $blurFactor));
	  $smallestHeight = ceil($originalHeight * pow(0.5, $blurFactor));
	
	  // for the first run, the previous image is the original input
	  $prevImage = $im;
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
	  imagecopyresized($im, $nextImage, 
	    0, 0, 0, 0, $originalWidth, $originalHeight, $nextWidth, $nextHeight);
	  imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
	
	  // clean up
	  imagedestroy($prevImage);
	
	  // return result
	  return $im;
	}
}