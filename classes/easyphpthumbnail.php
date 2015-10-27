<?php 

/**

EasyPhpThumbnail class version 2.0.4 - PHP5
On-the-fly image manipulation and thumbnail generation

Copyright (c) 2008-2010 JF Nutbroek <jfnutbroek@gmail.com>
Visit http://www.mywebmymail.com for more information and a commercial version

Permission to use, copy, modify, and/or distribute this software for any
purpose without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.

*/

class easyphpthumbnail {
	
	/**
	 * The size of the thumbnail in px
	 * Autoscale landscape or portrait
	 *
	 * @var int
	 */	
	public $Thumbsize;	
	/**
	 * The height of the thumbnail in px
	 * Forces all thumbnails to the same height
	 *
	 * @var int
	 */	
	public $Thumbheight;		
	/**
	 * The width of the thumbnail in px
	 * Forces all thumbnails to the same width
	 *
	 * @var int
	 */	
	public $Thumbwidth;
	/**
	 * Set dimensions to percentage instead of px
	 * 
	 * @var boolean
	 */		
	public $Percentage;	
	/**
	 * Allow image enlargement
	 *
	 * @var boolean
	 */		
	public $Inflate;
	/**
	 * Quality of JPEG images 0 - 100
	 *
	 * @var int
	 */		
	public $Quality;	
	/**
	 * The frame width in px around the image
	 *
	 * @var int
	 */	
	public $Framewidth;
	/**
	 * Frame color in web format: '#00FF00'
	 *
	 * @var string
	 */		
	public $Framecolor;	
	/**
	 * Background color in web format: '#00FF00'
	 *
	 * @var string
	 */		
	public $Backgroundcolor;	
	/**
	 * Add shadow
	 * 
	 * @var boolean
	 */		
	public $Shadow;
	/**
	 * Show binder rings
	 *
	 * @var boolean
	 */		
	public $Binder;
	/**
	 * Binder ring spacing in px
	 *
	 * @var int
	 */			
	public $Binderspacing;	
	/**
	 * Path to PNG watermark image
	 *
	 * @var string
	 */		
	public $Watermarkpng;
	/**
	 * Position of watermark image, bottom right corner: '100% 100%'
	 *
	 * @var string
	 */		
	public $Watermarkposition;
	/**
	 * Transparency level of watermark image 0 - 100
	 *
	 * @var int
	 */		
	public $Watermarktransparency;
	/**
	 * CHMOD level of saved thumbnails: '0755'
	 *
	 * @var string
	 */		
	public $Chmodlevel;
	/**
	 * Path to location for thumbnails
	 *
	 * @var string
	 */		
	public $Thumblocation;
	/**
	 * Filetype conversion for saving thumbnail
	 *
	 * @var string
	 */		
	public $Thumbsaveas;	
	/**
	 * Prefix for saving thumbnails
	 *
	 * @var string
	 */		
	public $Thumbprefix;
	/**
	 * Clip corners; array with 7 values
	 * [0]: 0=disable 1=straight 2=rounded
	 * [1]: Percentage of clipping
	 * [2]: Clip randomly 0=disable 1=enable
	 * [3]: Clip top left 0=disable 1=enable
	 * [4]: Clip bottom left 0=disable 1=enable
	 * [5]: Clip top right 0=disable 1=enable
	 * [6]: Clip bottom right 0=disable 1=enable
	 *
	 * @var array
	 */		
	public $Clipcorner;
	/**
	 * Age image; array with 3 values
	 * [0]: Boolean 0=disable 1=enable
	 * [1]: Add noise 0-100, 0=disable
	 * [2]: Sephia depth 0-100, 0=disable (greyscale)
	 *
	 * @var array
	 */		
	public $Ageimage;
	/**
	 * Crop image; array with 6 values
	 * [0]: 0=disable 1=enable free crop 2=enable center crop 3=enable square crop
	 * [1]: 0=percentage 1=pixels
	 * [2]: Crop left
	 * [3]: Crop right
	 * [4]: Crop top
	 * [5]: Crop bottom
	 *
	 * @var array
	 */		
	public $Cropimage;	
	/**
	 * Path to PNG border image
	 *
	 * @var string
	 */			
	public $Borderpng;
	/**
	 * Copyright text
	 *
	 * @var string
	 */		
	public $Copyrighttext;
	/**
	 * Position for Copyrighttext text, bottom right corner: '100% 100%'
	 *
	 * @var string
	 */			
	public $Copyrightposition;
	/**
	 * Path to TTF Fonttype
	 * If no TTF font is specified, system font will be used
	 *
	 * @var string
	 */			
	public $Copyrightfonttype;		
	/**
	 * Fontsize for Copyrighttext text
	 *
	 * @var string
	 */			
	public $Copyrightfontsize;	
	/**
	 * Copyrighttext text color in web format: '#000000'
	 * No color specified will auto-determine black or white
	 *
	 * @var string
	 */			
	public $Copyrighttextcolor;
	/**
	 * Add text to the image
	 * [0]: 0=disable 1=enable
	 * [1]: Text string
	 * [2]: Position for text, bottom right corner: '100% 100%'
	 * [3]: Path to TTF Fonttype, if no TTF font is specified, system font will be used
	 * [4]: Fontsize for text
	 * [5]: Text color in web format: '#000000'
	 *
	 * @var array
	 */		
	public $Addtext;	
	/**
	 * Rotate image in degrees
	 *
	 * @var int
	 */				
	public $Rotate;	
	/**
	 * Flip the image horizontally
	 *
	 * @var boolean
	 */				
	public $Fliphorizontal;		
	/**
	 * Flip the image vertically
	 *
	 * @var boolean
	 */				
	public $Flipvertical;	
	/**
	 * Create square canvas thumbs
	 *
	 * @var boolean
	 */			
	public $Square;
	/**
	 * Apply a filter to the image
	 *
	 * @var boolean
	 */			
	public $Applyfilter;
	/**
	 * Apply a 3x3 filter matrix to the image; array with 9 values
	 * [0]: a1,1
	 * [1]: a1,2
	 * [2]: a1,3
	 * [3]: a2,1
	 * [4]: a2,2
	 * [5]: a2,3
	 * [6]: a3,1
	 * [7]: a3,2
	 * [8]: a3,3
	 *
	 * @var array
	 */		
	public $Filter;
	/**
	 * Divisor for filter
	 *
	 * @var int
	 */				
	public $Divisor;
	/**
	 * Offset for filter
	 *
	 * @var int
	 */				
	public $Offset;
	/**
	 * Blur filter
	 *
	 * @var boolean
	 */				
	public $Blur;	
	/**
	 * Sharpen filter
	 *
	 * @var boolean
	 */				
	public $Sharpen;		
	/**
	 * Edge filter
	 *
	 * @var boolean
	 */				
	public $Edge;
	/**
	 * Emboss filter
	 *
	 * @var boolean
	 */				
	public $Emboss;
	/**
	 * Mean filter
	 *
	 * @var boolean
	 */				
	public $Mean;	
	/**
	 * Rotate and crop the image
	 *
	 * @var boolean
	 */				
	public $Croprotate;	
	/**
	 * Apply perspective to the image; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Direction 0=left 1=right 2=top 3=bottom
	 * [2]: Perspective strength 0 - 100
	 *
	 * @var array
	 */		
	public $Perspective;
	/**
	 * Apply perspective to the thumbnail; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Direction 0=left 1=right 2=top 3=bottom
	 * [2]: Perspective strength 0 - 100
	 *
	 * @var array
	 */		
	public $Perspectivethumb;
	/**
	 * Apply shading gradient to the image; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Shading strength 0 - 100
	 * [2]: Shading area 0 - 100
	 * [3]: Shading direction 0=right 1=left 2=top 3=bottom
	 *
	 * @var array
	 */		
	public $Shading;
	/**
	 * Shading gradient color in web format: '#00FF00'
	 *
	 * @var string
	 */		
	public $Shadingcolor;		
	/**
	 * Apply a mirror effect to the thumbnail; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Mirror transparency gradient starting strength 0 - 100
	 * [2]: Mirror transparency gradient ending strength 0 - 100
	 * [3]: Mirror area 0 - 100
	 * [4]: Mirror 'gap' between original image and reflection in px
	 *
	 * @var array
	 */	
	public $Mirror;
	/**
	 * Mirror gradient color in web format: '#00FF00'
	 *
	 * @var string
	 */		
	public $Mirrorcolor;		
	/**
	 * Create image negative
	 *
	 * @var boolean
	 */			
	public $Negative;
	/**
	 * Replace a color in the image; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Color to replace in web format: '#00FF00'
	 * [2]: Replacement color in web format: '#FF0000'
	 * [3]: RGB tolerance 0 - 100
	 *
	 * @var array
	 */		
	public $Colorreplace;
	/**
	 * Scramble pixels; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Pixel range
	 * [2]: Repeats (use with care!)
	 *
	 * @var array
	 */		
	public $Pixelscramble;
	/**
	 * Convert image to greyscale
	 *
	 * @var boolean
	 */				
	public $Greyscale;
	/**
	 * Change brightness of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Brightness -100 to 100
	 *
	 * @var array
	 */		
	public $Brightness;	
	/**
	 * Change contrast of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Contrast -100 to 100
	 *
	 * @var array
	 */		
	public $Contrast;
	/**
	 * Change gamma of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Gamma correction factor
	 *
	 * @var array
	 */		
	public $Gamma;	
	/**
	 * Reduce palette of the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Amount of colors for palette
	 *
	 * @var array
	 */		
	public $Palette;	
	/**
	 * Merge a color in the image; array with 5 values
	 * [0]: 0=disable 1=enable
	 * [1]: Red component 0 - 255
	 * [2]: Green component 0 - 255
	 * [3]: Blue component 0 - 255
	 * [4]: Opacity level 0 - 127
	 *
	 * @var array
	 */		
	public $Colorize;	
	/**
	 * Pixelate the image; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Block size in px
	 *
	 * @var array
	 */		
	public $Pixelate;		
	/**
	 * Apply a median filter to remove noise
	 *
	 * @var boolean
	 */				
	public $Medianfilter;		
	/**
	 * Deform the image with twirl effect; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Effect strength 0 to 100
	 * [2]: Direction of twirl 0=clockwise 1=anti-clockwise
	 *
	 * @var array
	 */		
	public $Twirlfx;
	/**
	 * Deform the image with ripple effect; array with 2 values
	 * [0]: 0=disable 1=enable
	 * [1]: Amount of horizontal waves
	 * [2]: Amplitude of horizontal waves in px
	 * [3]: Amount of vertical waves
	 * [4]: Amplitude of vertical waves in px	 
	 *
	 * @var array
	 */		
	public $Ripplefx;		
	/**
	 * Deform the image with perspective ripple or 'lake' effect; array with 3 values
	 * [0]: 0=disable 1=enable
	 * [1]: Density of the waves	 
	 * [2]: Lake area measured from bottom 0 - 100	 
	 *
	 * @var array
	 */		
	public $Lakefx;
	/**
	 * Deform the image with a circular waterdrop effect; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: Amplitude in px
	 * [2]: Radius in px
	 * [3]: Wavelength in px
	 *
	 * @var array
	 */		
	public $Waterdropfx;
	/**
	 * Create transparent image; array with 4 values
	 * [0]: 0=disable 1=enable
	 * [1]: 0=PNG 1=GIF 2=Original File Format
	 * [2]: Replacement color in web format: '#FF0000'
	 * [3]: RGB tolerance 0 - 100
	 *
	 * @var array
	 */		
	public $Maketransparent;
	/**
	 * Keep transparency of original image
	 *
	 * @var boolean
	 */				
	public $Keeptransparency;	
	/**
	 * Filename for saving thumbnails
	 *
	 * @var string
	 */		
	public $Thumbfilename;	
	/**
	 * Create Polaroid Look
	 *
	 * @var boolean
	 */			
	public $Polaroid;
	/**
	 * Write text on Polaroid
	 *
	 * @var string
	 */			
	public $Polaroidtext;	
	/**
	 * Path to TTF Fonttype
	 *
	 * @var string
	 */			
	public $Polaroidfonttype;		
	/**
	 * Fontsize for polaroid text
	 *
	 * @var int
	 */			
	public $Polaroidfontsize;	
	/**
	 * Polaroid text color in web format: '#000000'
	 *
	 * @var string
	 */			
	public $Polaroidtextcolor;
	/**
	 * Polaroid frame color in web format: '#FFFFFF'
	 *
	 * @var string
	 */			
	public $Polaroidframecolor;		
	/**
	 * Deform the image with a displacement map; array with 7 values
	 * [0]: 0=disable 1=enable
	 * [1]: Path to displacement image (grey #808080 is neutral)
	 * [2]: 0=resize the map to fit the image 1=keep original map size
	 * [3]: X coordinate for map position in px 
	 * [4]: Y coordinate for map position in px 
	 * [5]: X displacement scale in px
	 * [6]: Y displacement scale in px
	 *
	 * @var array
	 */		
	public $Displacementmap;
	/**
	 * Deform the thumbnail with a displacement map; array with 7 values
	 * [0]: 0=disable 1=enable
	 * [1]: Path to displacement image (grey #808080 is neutral)
	 * [2]: 0=resize the map to fit the image 1=keep original map size
	 * [3]: X coordinate for map position in px 
	 * [4]: Y coordinate for map position in px 
	 * [5]: X displacement scale in px
	 * [6]: Y displacement scale in px
	 *
	 * @var array
	 */		
	public $Displacementmapthumb;	
	/**
	 * The image filename or array with filenames
	 *
	 * @var string / array
	 */	
	private $image;	
	/**
	 * Original image
	 *
	 * @var image	 
	 */			
	private $im;
	/**
	 * Thumbnail image
	 *
	 * @var image	 
	 */			
	private $thumb;
	/**
	 * Temporary image
	 *
	 * @var image	 
	 */			
	private $newimage;	
	/**
	 * Dimensions of original image; array with 3 values
	 * [0]: Width
	 * [1]: Height
	 * [2]: Filetype
	 *
	 * @var array	 
	 */			
	private $size;
	/**
	 * Offset in px for binder
	 *
	 * @var int	 
	 */			
	private $bind_offset;
	/**
	 * Offset in px for shadow
	 *
	 * @var int	 
	 */			
	private $shadow_offset;
	/**
	 * Offset in px for frame
	 *
	 * @var int 
	 */			
	private $frame_offset;
	/**
	 * Thumb width in px
	 *
	 * @var int	 
	 */				
	private $thumbx;
	/**
	 * Thumb height in px
	 *
	 * @var int	 
	 */				
	private $thumby;
	
	/** 
	 * The following functions are required 'core' functions, you cannot delete them.
	 * Refer to the next section to create your own 'lightweight' class.
	 *
	 */

	/**
	 * Class constructor
	 *
	 */	
	public function __construct() {
	
		$this->Thumbsize              = 160;
		$this->Thumbheight            = 0;
		$this->Thumbwidth             = 0;
		$this->Percentage             = false;		
		$this->Framewidth             = 0;
		$this->Inflate                = false;
		$this->Shadow                 = false;
		$this->Binder                 = false;
		$this->Binderspacing          = 8;		
		$this->Backgroundcolor        = '#FFFFFF';
		$this->Framecolor             = '#FFFFFF';
		$this->Watermarkpng           = '';
		$this->Watermarkposition      = '100% 100%';
		$this->Watermarktransparency  = '70';	
		$this->Quality                = '90';
		$this->Chmodlevel             = '';
		$this->Thumblocation          = '';
		$this->Thumbsaveas            = '';
		$this->Thumbprefix            = '';
		$this->Clipcorner             = array(0,15,0,1,1,1,0);
		$this->Ageimage               = array(0,10,80);
		$this->Cropimage              = array(0,0,20,20,20,20);		
		$this->Borderpng              = '';
		$this->Copyrighttext          = '';
		$this->Copyrightposition      = '0% 95%';
		$this->Copyrightfonttype      = '';
		$this->Copyrightfontsize      = 2;
		$this->Copyrighttextcolor     = '';
		$this->Addtext                = array(0,'Text','50% 50%','',2,'#000000');
		$this->Rotate                 = 0;
		$this->Fliphorizontal         = false;
		$this->Flipvertical           = false;
		$this->Square                 = false;
		$this->Applyfilter            = false;		
		$this->Filter                 = array(0,0,0,0,1,0,0,0,0);
		$this->Divisor                = 1;
		$this->Offset                 = 0;
		$this->Blur                   = false;		
		$this->Sharpen                = false;	
		$this->Edge                   = false;	
		$this->Emboss                 = false;	
		$this->Mean                   = false;			
		$this->Croprotate             = false;	
		$this->Perspective            = array(0,0,30);
		$this->Perspectivethumb       = array(0,1,20);
		$this->Shading                = array(0,70,65,0);
		$this->Shadingcolor           = '#000000';		
		$this->Mirror                 = array(0,20,100,40,2);
		$this->Mirrorcolor            = '#FFFFFF';		
		$this->Negative               = false;
		$this->Colorreplace           = array(0,'#000000','#FFFFFF',30);
		$this->Pixelscramble          = array(0,3,1);
		$this->Greyscale              = false;		
		$this->Brightness             = array(0,30);
		$this->Contrast               = array(0,30);
		$this->Gamma                  = array(0,1.5);
		$this->Palette                = array(0,6);
		$this->Colorize               = array(0,100,0,0,0);
		$this->Pixelate               = array(0,3);
		$this->Medianfilter           = false;
		$this->Twirlfx                = array(0,20,0);
		$this->Ripplefx               = array(0,5,15,5,5);
		$this->Lakefx                 = array(0,15,80);
		$this->Waterdropfx            = array(0,1.2,400,40);
		$this->Maketransparent        = array(0,0,'#FFFFFF',30);
		$this->Keeptransparency       = false;
		$this->Thumbfilename          = '';
		$this->Polaroid               = false;
		$this->Polaroidtext           = '';
		$this->Polaroidfonttype       = '';
		$this->Polaroidfontsize       = '30';
		$this->Polaroidtextcolor      = '#000000';
		$this->Polaroidframecolor     = '#FFFFFF';		
		$this->Displacementmap        = array(0,'',0,0,0,50,50);
		$this->Displacementmapthumb   = array(0,'',0,0,0,25,25);		
		
	}

	/**
	 * Class destructor
	 *
	 */	
	public function __destruct() {
	
		if(is_resource($this->im)) imagedestroy($this->im);
		if(is_resource($this->thumb)) imagedestroy($this->thumb);
		if(is_resource($this->newimage)) imagedestroy($this->newimage);
	
	}

	/**
	 * Creates and outputs thumbnail
	 *
	 * @param string/array $filename
	 * @param string $output
	 */	
	public function Createthumb($filename="unknown",$output="screen") {

		if (is_array($filename) && $output=="file") {
			foreach ($filename as $name) {
				$this->image=$name;
				$this->thumbmaker();
				$this->savethumb();
			}
		} else {
			$this->image=$filename;
			$this->thumbmaker();
			if ($output=="file") {$this->savethumb();} else {$this->displaythumb();}
		}
		
	}

	/**
	 * Apply all modifications to the image
	 *
	 */	
	private function thumbmaker() {

		if($this->loadimage()) {
			// Modifications to the original sized image			
			if ($this->Cropimage[0]>0) {$this->cropimage();}
			if ($this->Addtext[0]>0) {$this->addtext();}
			if ($this->Medianfilter) {$this->medianfilter();}
			if ($this->Greyscale) {$this->greyscale();}
			if ($this->Brightness[0]==1) {$this->brightness();}
			if ($this->Contrast[0]==1) {$this->contrast();}
			if ($this->Gamma[0]==1) {$this->gamma();}
			if ($this->Palette[0]==1) {$this->palette();}
			if ($this->Colorize[0]==1) {$this->colorize();}			
			if ($this->Colorreplace[0]==1) {$this->colorreplace();}
			if ($this->Pixelscramble[0]==1) {$this->pixelscramble();}
			if ($this->Pixelate[0]==1) {$this->pixelate();}
			if ($this->Ageimage[0]==1) {$this->ageimage();}
			if ($this->Fliphorizontal) {$this->rotateorflip(0,1);}
			if ($this->Flipvertical) {$this->rotateorflip(0,-1);}
			if ($this->Watermarkpng!='') {$this->addpngwatermark();}
			if ($this->Clipcorner[0]==1) {$this->clipcornersstraight();}
			if ($this->Clipcorner[0]==2) {$this->clipcornersround();}
			if (intval($this->Rotate)<>0 && !$this->Croprotate) {
				switch(intval($this->Rotate)) {
					case -90:
					case 270:
						$this->rotateorflip(1,0);
						break;
					case -270:
					case 90:
						$this->rotateorflip(1,0);
						break;
					case -180:
					case 180:
						$this->rotateorflip(1,0);
						$this->rotateorflip(1,0);
						break;
					default:
						$this->freerotate();
				}
			}
			if ($this->Croprotate) {$this->croprotate();}
			if ($this->Sharpen) {$this->sharpen();}			
			if ($this->Blur) {$this->blur();}
			if ($this->Edge) {$this->edge();}			
			if ($this->Emboss) {$this->emboss();}	
			if ($this->Mean) {$this->mean();}	
			if ($this->Applyfilter) {$this->filter();}
			if ($this->Twirlfx[0]==1) {$this->twirlfx();}
			if ($this->Ripplefx[0]==1) {$this->ripplefx();}
			if ($this->Lakefx[0]==1) {$this->lakefx();}
			if ($this->Waterdropfx[0]==1) {$this->waterdropfx();}
			if ($this->Displacementmap[0]==1) {$this->displace();}			
			if ($this->Negative) {$this->negative();}
			if ($this->Shading[0]==1) {$this->shading();}
			if ($this->Polaroid) {$this->polaroid();}			
			if ($this->Perspective[0]==1) {$this->perspective();}
			// Prepare the thumbnail (new canvas) and add modifications to the resized image (thumbnail)
			$this->createemptythumbnail();
			if ($this->Binder) {$this->addbinder();}
			if ($this->Shadow) {$this->addshadow();}
			imagecopyresampled($this->thumb,$this->im,$this->Framewidth*($this->frame_offset-1),$this->Framewidth,0,0,$this->thumbx-($this->frame_offset*$this->Framewidth)-$this->shadow_offset,$this->thumby-2*$this->Framewidth-$this->shadow_offset,imagesx($this->im),imagesy($this->im));
			if ($this->Borderpng!='') {$this->addpngborder();}
			if ($this->Copyrighttext!='') {$this->addcopyright();}		
			if ($this->Square) {$this->square();}
			if ($this->Mirror[0]==1) {$this->mirror();}
			if ($this->Displacementmapthumb[0]==1) {$this->displacethumb();}			
			if ($this->Perspectivethumb[0]==1) {$this->perspectivethumb();}
			if ($this->Maketransparent[0]==1) {$this->maketransparent();}
		}
		
	}

	/**
	 * Load image in memory
	 *
	 */	
	private function loadimage() {

		if (is_resource($this->im)) {
			return true;
		} else if (file_exists($this->image)) {
			$this->size=GetImageSize($this->image);
			switch($this->size[2]) {
				case 1:
					if (imagetypes() & IMG_GIF) {$this->im=imagecreatefromgif($this->image);return true;} else {$this->invalidimage('No GIF support');return false;}
					break;
				case 2:
					if (imagetypes() & IMG_JPG) {$this->im=imagecreatefromjpeg($this->image);$this->Keeptransparency=false;return true;} else {$this->invalidimage('No JPG support');return false;}
					break;
				case 3:
					if (imagetypes() & IMG_PNG) {$this->im=imagecreatefrompng($this->image);return true;} else {$this->invalidimage('No PNG support');return false;}
					break;
				default:
					$this->invalidimage('Filetype ?????');
					return false;
			}
		} else {
			$this->invalidimage('File not found');
			return false;
		}
				
	}

	/**
	 * Creates error image
	 *
	 * @param string $message
	 */	
	private function invalidimage($message) {
	
		$this->thumb=imagecreate(80,75);
		$black=imagecolorallocate($this->thumb,0,0,0);$yellow=imagecolorallocate($this->thumb,255,255,0);
		imagefilledrectangle($this->thumb,0,0,80,75,imagecolorallocate($this->thumb,255,0,0));
		imagerectangle($this->thumb,0,0,79,74,$black);imageline($this->thumb,0,20,80,20,$black);
		imagefilledrectangle($this->thumb,1,1,78,19,$yellow);imagefilledrectangle($this->thumb,27,35,52,60,$yellow);
		imagerectangle($this->thumb,26,34,53,61,$black);
		imageline($this->thumb,27,35,52,60,$black);imageline($this->thumb,52,35,27,60,$black);
		imagestring($this->thumb,1,5,5,$message,$black);
		
	}		

	/**
	 * Create empty thumbnail
	 *
	 */	
	private function createemptythumbnail() {
	
		$thumbsize=$this->Thumbsize;$thumbwidth=$this->Thumbwidth;$thumbheight=$this->Thumbheight;
		if ($thumbsize==0) {$thumbsize=9999;$thumbwidth=0;$thumbheight=0;}
		if ($this->Percentage) {
			if ($thumbwidth>0) {$thumbwidth=floor(($thumbwidth/100)*$this->size[0]);}
			if ($thumbheight>0) {$thumbheight=floor(($thumbheight/100)*$this->size[1]);}
			if ($this->size[0]>$this->size[1])
				$thumbsize=floor(($thumbsize/100)*$this->size[0]);
			else
				$thumbsize=floor(($thumbsize/100)*$this->size[1]);
		}
		if (!$this->Inflate) {
			if ($thumbsize>$this->size[0] && $thumbsize>$this->size[1]) {$thumbsize=max($this->size[0],$this->size[1]);}
			if ($thumbheight>$this->size[1]) {$thumbheight=$this->size[1];}
			if ($thumbwidth>$this->size[0]) {$thumbwidth=$this->size[0];}
		}
		if ($this->Binder) {$this->frame_offset=3;$this->bind_offset=4;} else {$this->frame_offset=2;$this->bind_offset=0;}
		if ($this->Shadow) {$this->shadow_offset=3;} else {$this->shadow_offset=0;}
		if ($thumbheight>0 && $thumbwidth>0) {
			$this->thumb=imagecreatetruecolor($this->Framewidth*$this->frame_offset+$thumbwidth+$this->shadow_offset,$this->Framewidth*2+$thumbheight+$this->shadow_offset);		
		} else if ($thumbheight>0) {
			$this->thumb=imagecreatetruecolor($this->Framewidth*$this->frame_offset+ceil($this->size[0]/($this->size[1]/$thumbheight))+$this->shadow_offset,$this->Framewidth*2+$thumbheight+$this->shadow_offset);
		} else if ($thumbwidth>0) {
			$this->thumb=imagecreatetruecolor($this->Framewidth*$this->frame_offset+$thumbwidth+$this->shadow_offset,$this->Framewidth*2+ceil($this->size[1]/($this->size[0]/$thumbwidth))+$this->shadow_offset);
		} else {
			$x1=$this->Framewidth*$this->frame_offset+$thumbsize+$this->shadow_offset;
			$x2=$this->Framewidth*$this->frame_offset+ceil($this->size[0]/($this->size[1]/$thumbsize))+$this->shadow_offset;
			$y1=$this->Framewidth*2+ceil($this->size[1]/($this->size[0]/$thumbsize))+$this->shadow_offset;
			$y2=$this->Framewidth*2+$thumbsize+$this->shadow_offset;
			if ($this->size[0]>$this->size[1]) {$this->thumb=imagecreatetruecolor($x1,$y1);} else {$this->thumb=imagecreatetruecolor($x2,$y2);}
		}
		$this->thumbx=imagesx($this->thumb);$this->thumby=imagesy($this->thumb);
		if ($this->Keeptransparency) {
			$alpha=imagecolortransparent($this->im);
			if ($aplha>=0) {
				$color=imagecolorsforindex($this->im,$alpha);
				$color_index=imagecolorallocate($this->thumb,$color['red'],$color['green'],$color['blue']);
				imagefill($this->thumb,0,0,$color_index);
				imagecolortransparent($this->thumb,$color_index);
			} else {
				imagealphablending($this->thumb,false);
				$color_alpha=imagecolorallocatealpha($this->im,0,0,0,127);
				imagefill($this->thumb,0,0,$color_alpha);
				imagesavealpha($this->thumb,true);
				imagealphablending($this->thumb,true);
			}
		} else {
			imagefilledrectangle($this->thumb,0,0,$this->thumbx,$this->thumby,imagecolorallocate($this->thumb,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));			
			if ($this->Polaroid)
				imagefilledrectangle($this->thumb,$this->bind_offset,0,$this->thumbx-$this->shadow_offset,$this->thumby-$this->shadow_offset,imagecolorallocate($this->thumb,hexdec(substr($this->Polaroidframecolor,1,2)),hexdec(substr($this->Polaroidframecolor,3,2)),hexdec(substr($this->Polaroidframecolor,5,2))));
			else			
				imagefilledrectangle($this->thumb,$this->bind_offset,0,$this->thumbx-$this->shadow_offset,$this->thumby-$this->shadow_offset,imagecolorallocate($this->thumb,hexdec(substr($this->Framecolor,1,2)),hexdec(substr($this->Framecolor,3,2)),hexdec(substr($this->Framecolor,5,2))));
		}
		
	}

	/**
	 * Save thumbnail to file
	 *
	 */	
	private function savethumb() {
	
		if ($this->Thumbsaveas!='') {
			switch (strtolower($this->Thumbsaveas)) {
				case "gif":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".gif";
					$this->size[2]=1;
					break;
				case "jpg":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".jpg";
					$this->size[2]=2;
					break;
				case "jpeg":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".jpeg";
					$this->size[2]=2;
					break;			
				case "png":
					$this->image=substr($this->image,0,strrpos($this->image,'.')).".png";
					$this->size[2]=3;
					break;
			}
		}
		if ($this->Thumbfilename!='') {
			$this->image=$this->Thumbfilename;
		}		
		switch($this->size[2]) {
			case 1:
				imagegif($this->thumb,$this->Thumblocation.$this->Thumbprefix.basename($this->image));
				break;
			case 2:
				imagejpeg($this->thumb,$this->Thumblocation.$this->Thumbprefix.basename($this->image),$this->Quality);
				break;
			case 3:
				imagepng($this->thumb,$this->Thumblocation.$this->Thumbprefix.basename($this->image));
				break;
		}		
		if ($this->Chmodlevel!='') {chmod($this->Thumblocation.$this->Thumbprefix.basename($this->image),octdec($this->Chmodlevel));}
		imagedestroy($this->im);
		imagedestroy($this->thumb);
		
	}

	/**
	 * Display thumbnail on screen
	 *
	 */	
	private function displaythumb() {
		
		switch($this->size[2]) {
			case 1:
				header("Content-type: image/gif");imagegif($this->thumb);
				break;
			case 2:
				header("Content-type: image/jpeg");imagejpeg($this->thumb,'',$this->Quality);
				break;
			case 3:
				header("Content-type: image/png");imagepng($this->thumb);
				break;
		}
		imagedestroy($this->im);
		imagedestroy($this->thumb);
		exit;
		
	}
	
	/** 
	 * The following functions are optional functions, you can delete them to create your own lightweight class.
	 * When you delete a function remove also the reference in thumbmaker() and optionally in __construct and the variable declaration.
	 *
	 */

	/**
	 * Add watermark to image
	 *
	 */	
	private function addpngwatermark() {
	
		if (file_exists($this->Watermarkpng)) {
			$this->newimage=imagecreatefrompng($this->Watermarkpng);
			$wpos=explode(' ',str_replace('%','',$this->Watermarkposition));
			imagecopymerge($this->im,$this->newimage,min(max(imagesx($this->im)*($wpos[0]/100)-0.5*imagesx($this->newimage),0),imagesx($this->im)-imagesx($this->newimage)),min(max(imagesy($this->im)*($wpos[1]/100)-0.5*imagesy($this->newimage),0),imagesy($this->im)-imagesy($this->newimage)),0,0,imagesx($this->newimage),imagesy($this->newimage),intval($this->Watermarktransparency));
			imagedestroy($this->newimage);
		}
		
	}

	/**
	 * Drop shadow on thumbnail
	 *
	 */	
	private function addshadow() {
	
		$gray=imagecolorallocate($this->thumb,192,192,192);
		$middlegray=imagecolorallocate($this->thumb,158,158,158);
		$darkgray=imagecolorallocate($this->thumb,128,128,128);
		imagerectangle($this->thumb,$this->bind_offset,0,$this->thumbx-4,$this->thumby-4,$gray);
		imageline($this->thumb,$this->bind_offset,$this->thumby-3,$this->thumbx,$this->thumby-3,$darkgray);
		imageline($this->thumb,$this->thumbx-3,0,$this->thumbx-3,$this->thumby,$darkgray);
		imageline($this->thumb,$this->bind_offset+2,$this->thumby-2,$this->thumbx,$this->thumby-2,$middlegray);
		imageline($this->thumb,$this->thumbx-2,2,$this->thumbx-2,$this->thumby,$middlegray);
		imageline($this->thumb,$this->bind_offset+2,$this->thumby-1,$this->thumbx,$this->thumby-1,$gray);
		imageline($this->thumb,$this->thumbx-1,2,$this->thumbx-1,$this->thumby,$gray);
		
	}

	/**
	 * Clip corners original image
	 *
	 */	
	private function clipcornersstraight() {
	
		$clipsize=$this->Clipcorner[1];
		if ($this->size[0]>$this->size[1])
			$clipsize=floor($this->size[0]*(intval($clipsize)/100));
		else
			$clipsize=floor($this->size[1]*(intval($clipsize)/100));
		if (intval($clipsize)>0) {
			$bgcolor=imagecolorallocate($this->im,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2)));
			if ($this->Clipcorner[2]) {$random1=rand(0,1);$random2=rand(0,1);$random3=rand(0,1);$random4=rand(0,1);} else {$random1=1;$random2=1;$random3=1;$random4=1;}
			for ($i=0;$i<$clipsize;$i++) {			
				if ($this->Clipcorner[3] && $random1) {imageline($this->im,0,$i,$clipsize-$i,$i,$bgcolor);}
				if ($this->Clipcorner[4] && $random2) {imageline($this->im,0,$this->size[1]-$i-1,$clipsize-$i,$this->size[1]-$i-1,$bgcolor);}				
				if ($this->Clipcorner[5] && $random3) {imageline($this->im,$this->size[0]-$clipsize+$i,$i,$this->size[0]+$clipsize-$i,$i,$bgcolor);}				
				if ($this->Clipcorner[6] && $random4) {imageline($this->im,$this->size[0]-$clipsize+$i,$this->size[1]-$i-1,$this->size[0]+$clipsize-$i,$this->size[1]-$i-1,$bgcolor);}
			}
		}
		
	}

	/**
	 * Clip round corners original image
	 *
	 */	
	private function clipcornersround() {
	
		$clipsize=floor($this->size[0]*($this->Clipcorner[1]/100));
		$clip_degrees=90/max($clipsize,1);
		$points_tl=array(0,0);
		$points_br=array($this->size[0],$this->size[1]);
		$points_tr=array($this->size[0],0);
		$points_bl=array(0,$this->size[1]);
		$bgcolor=imagecolorallocate($this->im,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2)));
		for ($i=0;$i<$clipsize;$i++) {
			$x=$clipsize*cos(deg2rad($i*$clip_degrees));
			$y=$clipsize*sin(deg2rad($i*$clip_degrees));
			array_push($points_tl,$clipsize-$x);
			array_push($points_tl,$clipsize-$y);
			array_push($points_tr,$this->size[0]-$clipsize+$x);
			array_push($points_tr,$clipsize-$y);
			array_push($points_br,$this->size[0]-$clipsize+$x);
			array_push($points_br,$this->size[1]-$clipsize+$y);
			array_push($points_bl,$clipsize-$x);
			array_push($points_bl,$this->size[1]-$clipsize+$y);
		}
		array_push($points_tl,$clipsize,0);
		array_push($points_br,$this->size[0]-$clipsize,$this->size[1]);
		array_push($points_tr,$this->size[0]-$clipsize,0);
		array_push($points_bl,$clipsize,$this->size[1]);
		if ($this->Clipcorner[2]) {$random1=rand(0,1);$random2=rand(0,1);$random3=rand(0,1);$random4=rand(0,1);} else {$random1=1;$random2=1;$random3=1;$random4=1;}
		if ($this->Clipcorner[3] && $random1) {imagefilledpolygon($this->im,$points_tl,count($points_tl)/2,$bgcolor);}
		if ($this->Clipcorner[4] && $random2) {imagefilledpolygon($this->im,$points_bl,count($points_bl)/2,$bgcolor);}		
		if ($this->Clipcorner[5] && $random3) {imagefilledpolygon($this->im,$points_tr,count($points_tr)/2,$bgcolor);}		
		if ($this->Clipcorner[6] && $random4) {imagefilledpolygon($this->im,$points_br,count($points_br)/2,$bgcolor);}

	}

	/**
	 * Convert original image to greyscale and/or apply noise and sephia effect
	 *
	 */	
	private function ageimage() {
	
		imagetruecolortopalette($this->im,1,256);
		for ($c=0;$c<256;$c++) {    
			$col=imagecolorsforindex($this->im,$c);
			$new_col=floor($col['red']*0.2125+$col['green']*0.7154+$col['blue']*0.0721);
			$noise=rand(-$this->Ageimage[1],$this->Ageimage[1]);
			if ($this->Ageimage[2]>0) {
				$r=$new_col+$this->Ageimage[2]+$noise;
				$g=floor($new_col+$this->Ageimage[2]/1.86+$noise);
				$b=floor($new_col+$this->Ageimage[2]/-3.48+$noise);
			} else {
				$r=$new_col+$noise;
				$g=$new_col+$noise;
				$b=$new_col+$noise;
			}
			imagecolorset($this->im,$c,max(0,min(255,$r)),max(0,min(255,$g)),max(0,min(255,$b)));
		}
		
	}

	/**
	 * Add border to thumbnail
	 *
	 */	
	private function addpngborder() {
	
		if (file_exists($this->Borderpng)) {
			$borderim=imagecreatefrompng($this->Borderpng);
			imagecopyresampled($this->thumb,$borderim,$this->bind_offset,0,0,0,$this->thumbx-$this->shadow_offset-$this->bind_offset,$this->thumby-$this->shadow_offset,imagesx($borderim),imagesy($borderim));
			imagedestroy($borderim);
		}
		
	}

	/**
	 * Add binder effect to thumbnail
	 *
	 */	
	private function addbinder() {
	
		if (intval($this->Binderspacing)<4) {$this->Binderspacing=4;}
		$spacing=floor($this->thumby/$this->Binderspacing)-2;
		$offset=floor(($this->thumby-($spacing*$this->Binderspacing))/2);
		$gray=imagecolorallocate($this->thumb,192,192,192);
		$middlegray=imagecolorallocate($this->thumb,158,158,158);
		$darkgray=imagecolorallocate($this->thumb,128,128,128);		
		$black=imagecolorallocate($this->thumb,0,0,0);	
		$white=imagecolorallocate($this->thumb,255,255,255);		
		for ($i=$offset;$i<=$offset+$spacing*$this->Binderspacing;$i+=$this->Binderspacing) {
			imagefilledrectangle($this->thumb,8,$i-2,10,$i+2,$black);
			imageline($this->thumb,11,$i-1,11,$i+1,$darkgray);
			imageline($this->thumb,8,$i-2,10,$i-2,$darkgray);
			imageline($this->thumb,8,$i+2,10,$i+2,$darkgray);
			imagefilledrectangle($this->thumb,0,$i-1,8,$i+1,$gray);
			imageline($this->thumb,0,$i,8,$i,$white);
			imageline($this->thumb,0,$i-1,0,$i+1,$gray);
			imagesetpixel($this->thumb,0,$i,$darkgray);
		}
		
	}

	/**
	 * Add Copyright text to thumbnail
	 *
	 */	
	private function addcopyright() {

		if ($this->Copyrightfonttype=='') {
			$widthx=imagefontwidth($this->Copyrightfontsize)*strlen($this->Copyrighttext);
			$heighty=imagefontheight($this->Copyrightfontsize);
			$fontwidth=imagefontwidth($this->Copyrightfontsize);
		} else {		
			$dimensions=imagettfbbox($this->Copyrightfontsize,0,$this->Copyrightfonttype,$this->Copyrighttext);
			$widthx=$dimensions[2];$heighty=$dimensions[5];
			$dimensions=imagettfbbox($this->Copyrightfontsize,0,$this->Copyrightfonttype,'W');
			$fontwidth=$dimensions[2];
		}
		$cpos=explode(' ',str_replace('%','',$this->Copyrightposition));
		if (count($cpos)>1) {
			$cposx=floor(min(max($this->thumbx*($cpos[0]/100)-0.5*$widthx,$fontwidth),$this->thumbx-$widthx-0.5*$fontwidth));
			$cposy=floor(min(max($this->thumby*($cpos[1]/100)-0.5*$heighty,$heighty),$this->thumby-$heighty*1.5));
		} else {
			$cposx=$fontwidth;
			$cposy=$this->thumby-10;
		}			
		if ($this->Copyrighttextcolor=='') {
			$colors=array();
			for ($i=$cposx;$i<($cposx+$widthx);$i++) {
				$indexis=ImageColorAt($this->thumb,$i,$cposy+0.5*$heighty);
				$rgbarray=ImageColorsForIndex($this->thumb,$indexis);
				array_push($colors,$rgbarray['red'],$rgbarray['green'],$rgbarray['blue']);
			}
			if (array_sum($colors)/count($colors)>180) {
				if ($this->Copyrightfonttype=='')
					imagestring($this->thumb,$this->Copyrightfontsize,$cposx,$cposy,$this->Copyrighttext,imagecolorallocate($this->thumb,0,0,0));
				else
					imagettftext($this->thumb,$this->Copyrightfontsize,0,$cposx,$cposy,imagecolorallocate($this->thumb,0,0,0),$this->Copyrightfonttype,$this->Copyrighttext);
			} else {
				if ($this->Copyrightfonttype=='')
					imagestring($this->thumb,$this->Copyrightfontsize,$cposx,$cposy,$this->Copyrighttext,imagecolorallocate($this->thumb,255,255,255));
				else
					imagettftext($this->thumb,$this->Copyrightfontsize,0,$cposx,$cposy,imagecolorallocate($this->thumb,255,255,255),$this->Copyrightfonttype,$this->Copyrighttext);				
			}
		} else {
			if ($this->Copyrightfonttype=='')
				imagestring($this->thumb,$this->Copyrightfontsize,$cposx,$cposy,$this->Copyrighttext,imagecolorallocate($this->thumb,hexdec(substr($this->Copyrighttextcolor,1,2)),hexdec(substr($this->Copyrighttextcolor,3,2)),hexdec(substr($this->Copyrighttextcolor,5,2))));
			else
				imagettftext($this->thumb,$this->Copyrightfontsize,0,$cposx,$cposy,imagecolorallocate($this->thumb,hexdec(substr($this->Copyrighttextcolor,1,2)),hexdec(substr($this->Copyrighttextcolor,3,2)),hexdec(substr($this->Copyrighttextcolor,5,2))),$this->Copyrightfonttype,$this->Copyrighttext);				
		}
		
	}

	/**
	 * Add text to image
	 *
	 */	
	private function addtext() {

		if ($this->Addtext[3]=='') {
			$widthx=imagefontwidth($this->Addtext[4])*strlen($this->Addtext[1]);
			$heighty=imagefontheight($this->Addtext[4]);
			$fontwidth=imagefontwidth($this->Addtext[4]);
		} else {		
			$dimensions=imagettfbbox($this->Addtext[4],0,$this->Addtext[3],$this->Addtext[1]);
			$widthx=$dimensions[2];$heighty=$dimensions[5];
			$dimensions=imagettfbbox($this->Addtext[4],0,$this->Addtext[3],'W');
			$fontwidth=$dimensions[2];
		}
		$cpos=explode(' ',str_replace('%','',$this->Addtext[2]));
		if (count($cpos)>1) {
			$cposx=floor(min(max($this->size[0]*($cpos[0]/100)-0.5*$widthx,$fontwidth),$this->size[0]-$widthx-0.5*$fontwidth));
			$cposy=floor(min(max($this->size[1]*($cpos[1]/100)-0.5*$heighty,$heighty),$this->size[1]-$heighty*1.5));
		} else {
			$cposx=$fontwidth;
			$cposy=$this->size[1]-10;
		}			
		if ($this->Addtext[3]=='')
			imagestring($this->im,$this->Addtext[4],$cposx,$cposy,$this->Addtext[1],imagecolorallocate($this->im,hexdec(substr($this->Addtext[5],1,2)),hexdec(substr($this->Addtext[5],3,2)),hexdec(substr($this->Addtext[5],5,2))));
		else
			imagettftext($this->im,$this->Addtext[4],0,$cposx,$cposy,imagecolorallocate($this->im,hexdec(substr($this->Addtext[5],1,2)),hexdec(substr($this->Addtext[5],3,2)),hexdec(substr($this->Addtext[5],5,2))),$this->Addtext[3],$this->Addtext[1]);
		
	}

	/**
	 * Rotate the image at any angle
	 * Image is not scaled down
	 *
	 */	
	private function freerotate() {
	
		$angle=$this->Rotate;
		if ($angle<>0) {
			$centerx=floor($this->size[0]/2);
			$centery=floor($this->size[1]/2);
			$maxsizex=ceil(abs(cos(deg2rad($angle))*$this->size[0])+abs(sin(deg2rad($angle))*$this->size[1]));
			$maxsizey=ceil(abs(sin(deg2rad($angle))*$this->size[0])+abs(cos(deg2rad($angle))*$this->size[1]));
			if ($maxsizex & 1) {$maxsizex+=3;} else	{$maxsizex+=2;}
			if ($maxsizey & 1) {$maxsizey+=3;} else {$maxsizey+=2;}
			$this->newimage=imagecreatetruecolor($maxsizex,$maxsizey);
			imagefilledrectangle($this->newimage,0,0,$maxsizex,$maxsizey,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));			
			$newcenterx=imagesx($this->newimage)/2;
			$newcentery=imagesy($this->newimage)/2;
			$angle+=180;
			for ($px=0;$px<imagesx($this->newimage);$px++) {
				for ($py=0;$py<imagesy($this->newimage);$py++) {
					$vectorx=floor(($newcenterx-$px)*cos(deg2rad($angle))+($newcentery-$py)*sin(deg2rad($angle)));
					$vectory=floor(($newcentery-$py)*cos(deg2rad($angle))-($newcenterx-$px)*sin(deg2rad($angle)));
					if (($centerx+$vectorx)>-1 && ($centerx+$vectorx)<($centerx*2) && ($centery+$vectory)>-1 && ($centery+$vectory)<($centery*2))
					    imagecopy($this->newimage,$this->im,$px,$py,$centerx+$vectorx,$centery+$vectory,1,1);
				}
			}
			imagedestroy($this->im);
			$this->im=imagecreatetruecolor(imagesx($this->newimage),imagesy($this->newimage));
			imagecopy($this->im,$this->newimage,0,0,0,0,imagesx($this->newimage),imagesy($this->newimage));
			imagedestroy($this->newimage);
			$this->size[0]=imagesx($this->im);
			$this->size[1]=imagesy($this->im);
		}
		
	}	

	/**
	 * Rotate the image at any angle
	 * Image is scaled down
	 *
	 */	
	private function croprotate() {
	
		$this->im=imagerotate($this->im,-$this->Rotate,imagecolorallocate($this->im,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));
		
	}
	
	/**
	 * Rotate the image +90, -90 or 180 degrees
	 * Flip the image over horizontal or vertical axis
	 *
	 * @param $rotate
	 * @param $flip
	 */		
	private function rotateorflip($rotate,$flip) {

		if ($rotate) {
			$this->newimage=imagecreatetruecolor($this->size[1],$this->size[0]);
		} else {
			$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		}
		if (intval($this->Rotate)>0 || $flip>0) {
			for ($px=0;$px<$this->size[0];$px++) {
				if ($rotate) {
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$this->size[1]-$py-1,$px,$px,$py,1,1);}
				} else {
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$this->size[0]-$px-1,$py,$px,$py,1,1);}
				}
			}
		} else {
			for ($px=0;$px<$this->size[0];$px++) {
				if ($rotate) {				
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$py,$this->size[0]-$px-1,$px,$py,1,1);}
				} else {
					for ($py=0;$py<$this->size[1];$py++) {imagecopy($this->newimage,$this->im,$px,$this->size[1]-$py-1,$px,$py,1,1);}
				}					
			}
		}
		imagedestroy($this->im);
		$this->im=imagecreatetruecolor(imagesx($this->newimage),imagesy($this->newimage));
		imagecopy($this->im,$this->newimage,0,0,0,0,imagesx($this->newimage),imagesy($this->newimage));			
		imagedestroy($this->newimage);
		$this->size[0]=imagesx($this->im);
		$this->size[1]=imagesy($this->im);

	}
	
	/**
	 * Crop image in percentage, pixels or in a square
	 * Crop from sides or from center
	 * Negative value for bottom crop will enlarge the canvas
	 *
	 */		
	private function cropimage() {	
		
		if ($this->Cropimage[1]==0) {
			$crop2=floor($this->size[0]*($this->Cropimage[2]/100));
			$crop3=floor($this->size[0]*($this->Cropimage[3]/100));
			$crop4=floor($this->size[1]*($this->Cropimage[4]/100));
			$crop5=floor($this->size[1]*($this->Cropimage[5]/100));
		} 
		if ($this->Cropimage[1]==1) {
			$crop2=$this->Cropimage[2];
			$crop3=$this->Cropimage[3];
			$crop4=$this->Cropimage[4];
			$crop5=$this->Cropimage[5];		
		}
		if ($this->Cropimage[0]==2) {
			$crop2=floor($this->size[0]/2)-$crop2;
			$crop3=floor($this->size[0]/2)-$crop3;
			$crop4=floor($this->size[1]/2)-$crop4;
			$crop5=floor($this->size[1]/2)-$crop5;
		}
		if ($this->Cropimage[0]==3) {
			if ($this->size[0]>$this->size[1]) {
				$crop2=$crop3=floor(($this->size[0]-$this->size[1])/2);
				$crop4=$crop5=0;
			} else {
				$crop4=$crop5=floor(($this->size[1]-$this->size[0])/2);
				$crop2=$crop3=0;			
			}
		}
		$this->newimage=imagecreatetruecolor($this->size[0]-$crop2-$crop3,$this->size[1]-$crop4-$crop5);
		if ($crop5<0) {$crop5=0;imagefilledrectangle($this->newimage,0,0,imagesx($this->newimage),imagesy($this->newimage),imagecolorallocate($this->newimage,hexdec(substr($this->Polaroidframecolor,1,2)),hexdec(substr($this->Polaroidframecolor,3,2)),hexdec(substr($this->Polaroidframecolor,5,2))));}
		imagecopy($this->newimage,$this->im,0,0,$crop2,$crop4,$this->size[0]-$crop2-$crop3,$this->size[1]-$crop4-$crop5);
		imagedestroy($this->im);
		$this->im=imagecreatetruecolor(imagesx($this->newimage),imagesy($this->newimage));
		imagecopy($this->im,$this->newimage,0,0,0,0,imagesx($this->newimage),imagesy($this->newimage));
		imagedestroy($this->newimage);
		$this->size[0]=imagesx($this->im);
		$this->size[1]=imagesy($this->im);
	
	}

	/**
	 * Enlarge the canvas to be same width and height
	 *
	 */	
	private function square() {
	
		$squaresize=max($this->thumbx,$this->thumby);
		$this->newimage=imagecreatetruecolor($squaresize,$squaresize);
		imagefilledrectangle($this->newimage,0,0,$squaresize,$squaresize,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));
		$centerx=floor(($squaresize-$this->thumbx)/2);
		$centery=floor(($squaresize-$this->thumby)/2);
		imagecopy($this->newimage,$this->thumb,$centerx,$centery,0,0,$this->thumbx,$this->thumby);
		imagedestroy($this->thumb);
		$this->thumb=imagecreatetruecolor($squaresize,$squaresize);
		imagecopy($this->thumb,$this->newimage,0,0,0,0,$squaresize,$squaresize);
		imagedestroy($this->newimage);
		
	}

	/**
	 * Apply a 3x3 filter matrix to the image
	 *
	 */	
	private function filter() {
		
		if (function_exists('imageconvolution')) {
			imageconvolution($this->im,array(array($this->Filter[0],$this->Filter[1],$this->Filter[2]), array($this->Filter[3],$this->Filter[4],$this->Filter[5]),array($this->Filter[6],$this->Filter[7],$this->Filter[8])),$this->Divisor,$this->Offset);	
		} else {
			$newpixel=array();
			$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$newpixel[0]=0;$newpixel[1]=0;$newpixel[2]=0;
					$a11=$this->rgbpixel($x-1,$y-1);$a12=$this->rgbpixel($x,$y-1);$a13=$this->rgbpixel($x+1,$y-1);
					$a21=$this->rgbpixel($x-1,$y);$a22=$this->rgbpixel($x,$y);$a23=$this->rgbpixel($x+1,$y);
					$a31=$this->rgbpixel($x-1,$y+1);$a32=$this->rgbpixel($x,$y+1);$a33=$this->rgbpixel($x+1,$y+1);
					$newpixel[0]+=$a11['red']*$this->Filter[0]+$a12['red']*$this->Filter[1]+$a13['red']*$this->Filter[2];
					$newpixel[1]+=$a11['green']*$this->Filter[0]+$a12['green']*$this->Filter[1]+$a13['green']*$this->Filter[2];
					$newpixel[2]+=$a11['blue']*$this->Filter[0]+$a12['blue']*$this->Filter[1]+$a13['blue']*$this->Filter[2];
					$newpixel[0]+=$a21['red']*$this->Filter[3]+$a22['red']*$this->Filter[4]+$a23['red']*$this->Filter[5];
					$newpixel[1]+=$a21['green']*$this->Filter[3]+$a22['green']*$this->Filter[4]+$a23['green']*$this->Filter[5];
					$newpixel[2]+=$a21['blue']*$this->Filter[3]+$a22['blue']*$this->Filter[4]+$a23['blue']*$this->Filter[5];
					$newpixel[0]+=$a31['red']*$this->Filter[6]+$a32['red']*$this->Filter[7]+$a33['red']*$this->Filter[8];
					$newpixel[1]+=$a31['green']*$this->Filter[6]+$a32['green']*$this->Filter[7]+$a33['green']*$this->Filter[8];
					$newpixel[2]+=$a31['blue']*$this->Filter[6]+$a32['blue']*$this->Filter[7]+$a33['blue']*$this->Filter[8];
					$newpixel[0]=max(0,min(255,intval($newpixel[0]/$this->Divisor)+$this->Offset));
					$newpixel[1]=max(0,min(255,intval($newpixel[1]/$this->Divisor)+$this->Offset));
					$newpixel[2]=max(0,min(255,intval($newpixel[2]/$this->Divisor)+$this->Offset));
					imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpixel[0],$newpixel[1],$newpixel[2],$a11['alpha']));
				}
			}
			imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
			imagedestroy($this->newimage);
		}
		
	}
	
	/**
	 * Apply a median filter matrix to the image to remove noise
	 *
	 */	
	private function medianfilter() {
		
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newred=array();$newgreen=array();$newblue=array();
				$a11=$this->rgbpixel($x-1,$y-1);$a12=$this->rgbpixel($x,$y-1);$a13=$this->rgbpixel($x+1,$y-1);
				$a21=$this->rgbpixel($x-1,$y);$a22=$this->rgbpixel($x,$y);$a23=$this->rgbpixel($x+1,$y);
				$a31=$this->rgbpixel($x-1,$y+1);$a32=$this->rgbpixel($x,$y+1);$a33=$this->rgbpixel($x+1,$y+1);
				$newred[]=$a11['red'];$newgreen[]=$a11['green'];$newblue[]=$a11['blue'];
				$newred[]=$a12['red'];$newgreen[]=$a12['green'];$newblue[]=$a12['blue'];
				$newred[]=$a13['red'];$newgreen[]=$a13['green'];$newblue[]=$a13['blue'];
				$newred[]=$a21['red'];$newgreen[]=$a21['green'];$newblue[]=$a21['blue'];
				$newred[]=$a22['red'];$newgreen[]=$a22['green'];$newblue[]=$a22['blue'];
				$newred[]=$a23['red'];$newgreen[]=$a23['green'];$newblue[]=$a23['blue'];
				$newred[]=$a31['red'];$newgreen[]=$a31['green'];$newblue[]=$a31['blue'];
				$newred[]=$a32['red'];$newgreen[]=$a32['green'];$newblue[]=$a32['blue'];
				$newred[]=$a33['red'];$newgreen[]=$a33['green'];$newblue[]=$a33['blue'];
				sort($newred,SORT_NUMERIC);sort($newgreen,SORT_NUMERIC);sort($newblue,SORT_NUMERIC);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newred[4],$newgreen[4],$newblue[4],$a22['alpha']));		
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		
	}

	/**
	 * Return RGB values from pixel
	 *
	 */	
	private function rgbpixel($x,$y) {
			
		if ($x<0) {$x=0;}
		if ($x>=$this->size[0]) {$x=$this->size[0]-1;}
		if ($y<0) {$y=0;}
		if ($y>=$this->size[1]) {$y=$this->size[1]-1;}		
		$pixel=ImageColorAt($this->im,$x,$y);
		return array('red' => ($pixel >> 16 & 0xFF),'green' => ($pixel >> 8 & 0xFF),'blue' => ($pixel & 0xFF),'alpha' => ($pixel >>24 & 0xFF));
		
	}	

	/**
	 * Gaussian Blur Filter
	 *
	 */	
	private function blur() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(1,2,1,2,4,2,1,2,1);
		$this->Divisor = 16;
		$this->Offset  = 0;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Sharpen Filter
	 *
	 */	
	private function sharpen() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(-1,-1,-1,-1,16,-1,-1,-1,-1);
		$this->Divisor = 8;
		$this->Offset  = 0;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Edge Filter
	 *
	 */	
	private function edge() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(-1,-1,-1,-1,8,-1,-1,-1,-1);
		$this->Divisor = 1;
		$this->Offset  = 127;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Emboss Filter
	 *
	 */	
	private function emboss() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(2,0,0,0,-1,0,0,0,-1);
		$this->Divisor = 1;
		$this->Offset  = 127;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}

	/**
	 * Mean Filter
	 *
	 */	
	private function mean() {

		$oldfilter=$this->Filter;$olddivisor=$this->Divisor;$oldoffset=$this->Offset;
		$this->Filter  = array(1,1,1,1,1,1,1,1,1);
		$this->Divisor = 9;
		$this->Offset  = 0;
		$this->filter();
		$this->Filter  = $oldfilter;
		$this->Divisor = $olddivisor;
		$this->Offset  = $oldoffset;		
		
	}
	
	/**
	 * Apply perspective to the image
	 *
	 */	
	private function perspective() {
		
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		imagefilledrectangle($this->newimage,0,0,$this->size[0],$this->size[1],imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));			
		if ($this->Perspective[1]==0 || $this->Perspective[1]==1) {
                        $gradient=($this->size[1]-($this->size[1]*(max(100-$this->Perspective[2],1)/100)))/$this->size[0];
		        for ($c=0;$c<$this->size[0];$c++) {
			        if ($this->Perspective[1]==0) {
				    $length=$this->size[1]-(floor($gradient*$c));
			        } else {
				    $length=$this->size[1]-(floor($gradient*($this->size[0]-$c)));
			        }
				imagecopyresampled($this->newimage,$this->im,$c,floor(($this->size[1]-$length)/2),$c,0,1,$length,1,$this->size[1]);
		        }
		} else {
                        $gradient=($this->size[0]-($this->size[0]*(max(100-$this->Perspective[2],1)/100)))/$this->size[1];
		        for ($c=0;$c<$this->size[1];$c++) {
			        if ($this->Perspective[1]==2) {
				    $length=$this->size[0]-(floor($gradient*$c));
			        } else {
				    $length=$this->size[0]-(floor($gradient*($this->size[1]-$c)));
			        }
				imagecopyresampled($this->newimage,$this->im,floor(($this->size[0]-$length)/2),$c,0,$c,$length,1,$this->size[0],1);
		        }		
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
	
	}		

	 /**
	 * Apply perspective to the thumbnail
	 *
	 */	
	private function perspectivethumb() {
		
		$this->newimage=imagecreatetruecolor($this->thumbx,$this->thumby);
		imagefilledrectangle($this->newimage,0,0,$this->thumbx,$this->thumby,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));			
		if ($this->Perspectivethumb[1]==0 || $this->Perspectivethumb[1]==1) {
                        $gradient=($this->thumby-($this->thumby*(max(100-$this->Perspectivethumb[2],1)/100)))/$this->thumbx;
		        for ($c=0;$c<$this->thumbx;$c++) {
			        if ($this->Perspectivethumb[1]==0) {
				    $length=$this->thumby-(floor($gradient*$c));
			        } else {
				    $length=$this->thumby-(floor($gradient*($this->thumbx-$c)));
			        }
				imagecopyresampled($this->newimage,$this->thumb,$c,floor(($this->thumby-$length)/2),$c,0,1,$length,1,$this->thumby);
		        }
		} else {
                        $gradient=($this->thumbx-($this->thumbx*(max(100-$this->Perspectivethumb[2],1)/100)))/$this->thumby;
		        for ($c=0;$c<$this->thumby;$c++) {
			        if ($this->Perspectivethumb[1]==2) {
				    $length=$this->thumbx-(floor($gradient*$c));
			        } else {
				    $length=$this->thumbx-(floor($gradient*($this->thumby-$c)));
			        }
				imagecopyresampled($this->newimage,$this->thumb,floor(($this->thumbx-$length)/2),$c,0,$c,$length,1,$this->thumbx,1);
		        }		
		}
		imagecopy($this->thumb,$this->newimage,0,0,0,0,$this->thumbx,$this->thumby);
		imagedestroy($this->newimage);
	
	}		

	/**
	 * Apply gradient shading to image
	 *
	 */	
	private function shading() {
		
		if ($this->Shading[3]==0 || $this->Shading[3]==1) {		
			$this->newimage=imagecreatetruecolor(1,$this->size[1]);
			imagefilledrectangle($this->newimage,0,0,1,$this->size[1],imagecolorallocate($this->newimage,hexdec(substr($this->Shadingcolor,1,2)),hexdec(substr($this->Shadingcolor,3,2)),hexdec(substr($this->Shadingcolor,5,2))));
		} else {
			$this->newimage=imagecreatetruecolor($this->size[0],1);
			imagefilledrectangle($this->newimage,0,0,$this->size[0],1,imagecolorallocate($this->newimage,hexdec(substr($this->Shadingcolor,1,2)),hexdec(substr($this->Shadingcolor,3,2)),hexdec(substr($this->Shadingcolor,5,2))));			
		}
		if ($this->Shading[3]==0) {
			$shadingstrength=$this->Shading[1]/($this->size[0]*($this->Shading[2]/100));
			for ($c=$this->size[0]-floor(($this->size[0]*($this->Shading[2]/100)));$c<$this->size[0];$c++) { 
				$opacity=floor($shadingstrength*($c-($this->size[0]-floor(($this->size[0]*($this->Shading[2]/100)))))); 
				imagecopymerge($this->im,$this->newimage,$c,0,0,0,1,$this->size[1],max(min($opacity,100),0));
			}	
		} else if ($this->Shading[3]==1) {
			$shadingstrength=$this->Shading[1]/($this->size[0]*($this->Shading[2]/100));
			for ($c=0;$c<floor($this->size[0]*($this->Shading[2]/100));$c++) { 
				$opacity=floor($this->Shading[1]-($c*$shadingstrength));			 
				imagecopymerge($this->im,$this->newimage,$c,0,0,0,1,$this->size[1],max(min($opacity,100),0));
			}			
		} else if ($this->Shading[3]==2) {
			$shadingstrength=$this->Shading[1]/($this->size[1]*($this->Shading[2]/100));
			for ($c=0;$c<floor($this->size[1]*($this->Shading[2]/100));$c++) { 
				$opacity=floor($this->Shading[1]-($c*$shadingstrength));			 
				imagecopymerge($this->im,$this->newimage,0,$c,0,0,$this->size[0],1,max(min($opacity,100),0));
			}			
		} else {
			$shadingstrength=$this->Shading[1]/($this->size[1]*($this->Shading[2]/100));
			for ($c=$this->size[1]-floor(($this->size[1]*($this->Shading[2]/100)));$c<$this->size[1];$c++) { 
				$opacity=floor($shadingstrength*($c-($this->size[1]-floor(($this->size[1]*($this->Shading[2]/100)))))); 
				imagecopymerge($this->im,$this->newimage,0,$c,0,0,$this->size[0],1,max(min($opacity,100),0));
			}			
		}
		imagedestroy($this->newimage);
	
	}		

	/**
	 * Apply mirror effect to the thumbnail with gradient 
	 *
	 */	
	private function mirror() {
		
		$bottom=floor(($this->Mirror[3]/100)*$this->thumby)+$this->Mirror[4];
		$this->newimage=imagecreatetruecolor($this->thumbx,$this->thumby+$bottom);
		imagefilledrectangle($this->newimage,0,0,$this->thumbx,$this->thumby+$bottom,imagecolorallocate($this->newimage,hexdec(substr($this->Backgroundcolor,1,2)),hexdec(substr($this->Backgroundcolor,3,2)),hexdec(substr($this->Backgroundcolor,5,2))));
		imagecopy($this->newimage,$this->thumb,0,0,0,0,$this->thumbx,$this->thumby);
		imagedestroy($this->thumb);$this->thumb=imagecreatetruecolor($this->thumbx,$this->thumby+$bottom);
		imagecopy($this->thumb,$this->newimage,0,0,0,0,$this->thumbx,$this->thumby+$bottom);
		imagedestroy($this->newimage);$this->thumbx=imagesx($this->thumb);$this->thumby=imagesy($this->thumb);
		for ($px=0;$px<$this->thumbx;$px++) {
			for ($py=$this->thumby-($bottom*2)+$this->Mirror[4];$py<($this->thumby-$bottom);$py++) {imagecopy($this->thumb,$this->thumb,$px,$this->thumby-($py-($this->thumby-($bottom*2)))-1+$this->Mirror[4],$px,$py,1,1);}
		}
		$this->newimage=imagecreatetruecolor($this->thumbx,1);
		imagefilledrectangle($this->newimage,0,0,$this->thumbx,1,imagecolorallocate($this->newimage,hexdec(substr($this->Mirrorcolor,1,2)),hexdec(substr($this->Mirrorcolor,3,2)),hexdec(substr($this->Mirrorcolor,5,2))));	
		$shadingstrength=($this->Mirror[2]-$this->Mirror[1])/$bottom;
		for ($c=$this->thumby-$bottom;$c<$this->thumby;$c++) { 
			$opacity=$this->Mirror[1]+floor(($bottom-($this->thumby-$c))*$shadingstrength);
			imagecopymerge($this->thumb,$this->newimage,0,$c,0,0,$this->thumbx,1,max(min($opacity,100),0));
		}	
		imagedestroy($this->newimage);

	}

	/**
	 * Create a negative
	 *
	 */	
	private function negative() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_NEGATE);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,255-($pixel >> 16 & 0xFF),255-($pixel >> 8 & 0xFF),255-($pixel & 0xFF),$pixel >> 24 & 0xFF));
				}
			}
		}

	}
	
	/**
	 * Replace a color
	 * Eucledian color vector distance
	 *
	 */	
	private function colorreplace() {
		
		$red=hexdec(substr($this->Colorreplace[1],1,2));$green=hexdec(substr($this->Colorreplace[1],3,2));$blue=hexdec(substr($this->Colorreplace[1],5,2));
		$rednew=hexdec(substr($this->Colorreplace[2],1,2));$greennew=hexdec(substr($this->Colorreplace[2],3,2));$bluenew=hexdec(substr($this->Colorreplace[2],5,2));
		$tolerance=sqrt(pow($this->Colorreplace[3],2)+pow($this->Colorreplace[3],2)+pow($this->Colorreplace[3],2));
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$pixel=ImageColorAt($this->im,$x,$y);
				$redpix=($pixel >> 16 & 0xFF);$greenpix=($pixel >> 8 & 0xFF);$bluepix=($pixel & 0xFF);
				if (sqrt(pow($redpix-$red,2)+pow($greenpix-$green,2)+pow($bluepix-$blue,2))<$tolerance)
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$rednew,$greennew,$bluenew,$pixel >> 24 & 0xFF));	
			}
		}

	}	

	/**
	 * Randomly reposition pixels
	 *
	 */	
	private function pixelscramble() {
		
		for ($i=0;$i<$this->Pixelscramble[2];$i++) {
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newx=$x+rand(-$this->Pixelscramble[1],$this->Pixelscramble[1]);
				$newy=$y+rand(-$this->Pixelscramble[1],$this->Pixelscramble[1]);
				if ($newx<0 && $newx>=$this->size[0]) {$newx=$x;}
				if ($newy<0 && $newy>=$this->size[1]) {$newy=$y;}
				imagecopy($this->newimage,$this->im,$newx,$newy,$x,$y,1,1);
				imagecopy($this->newimage,$this->im,$x,$y,$newx,$newy,1,1);
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		}
		
	}

	/**
	 * Convert to greyscale
	 *
	 */	
	private function greyscale() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_GRAYSCALE);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$grey=floor(($pixel >> 16 & 0xFF)*0.299 + ($pixel >> 8 & 0xFF)*0.587 + ($pixel & 0xFF)*0.114);
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$grey,$grey,$grey,$pixel >> 24 & 0xFF));
				}
			}
		}		

	}

	/**
	 * Change brightness
	 *
	 */	
	private function brightness() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_BRIGHTNESS,$this->Brightness[1]);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$redpix=max(0,min(255,($pixel >> 16 & 0xFF)+($this->Brightness[1]/100)*255));
					$greenpix=max(0,min(255,($pixel >> 8 & 0xFF)+($this->Brightness[1]/100)*255));
					$bluepix=max(0,min(255,($pixel & 0xFF)+($this->Brightness[1]/100)*255));
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$redpix,$greenpix,$bluepix,$pixel >> 24 & 0xFF));
				}
			}
		}		

	}

	/**
	 * Change contrast
	 *
	 */	
	private function contrast() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_CONTRAST,-$this->Contrast[1]);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$redpix=max(0,min(255,(((($pixel >> 16 & 0xFF)/255)-0.5)*($this->Contrast[1]/100+1)+0.5)*255));
					$greenpix=max(0,min(255,(((($pixel >> 8 & 0xFF)/255)-0.5)*($this->Contrast[1]/100+1)+0.5)*255));
					$bluepix=max(0,min(255,(((($pixel & 0xFF)/255)-0.5)*($this->Contrast[1]/100+1)+0.5)*255));
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$redpix,$greenpix,$bluepix,$pixel >> 24 & 0xFF));
				}
			}
		}		

	}

	/**
	 * Change gamma
	 *
	 */	
	private function gamma() {
		
		imagegammacorrect($this->im,1,$this->Gamma[1]);	

	}

	/**
	 * Reduce palette
	 *
	 */	
	private function palette() {
		
		imagetruecolortopalette($this->im,false,$this->Palette[1]);

	}

	/**
	 * Merge a color in the image
	 *
	 */	
	private function colorize() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_COLORIZE,$this->Colorize[1],$this->Colorize[2],$this->Colorize[3],$this->Colorize[4]);
		} else {
			for ($y=0;$y<$this->size[1];$y++) {
				for ($x=0;$x<$this->size[0];$x++) {
					$pixel=ImageColorAt($this->im,$x,$y);
					$redpix=max(0,min(255,($pixel >> 16 & 0xFF)+$this->Colorize[1]));
					$greenpix=max(0,min(255,($pixel >> 8 & 0xFF)+$this->Colorize[2]));
					$bluepix=max(0,min(255,($pixel & 0xFF)+$this->Colorize[3]));
					$alpha =max(0,min(127,($pixel >> 24 & 0xFF)+$this->Colorize[4]));
					imagesetpixel($this->im,$x,$y,imagecolorallocatealpha($this->im,$redpix,$greenpix,$bluepix,$alpha));
				}
			}
		}		

	}

	/**
	 * Pixelate the image
	 *
	 */	
	private function pixelate() {
		
		if (function_exists('imagefilter')) {
			imagefilter($this->im,IMG_FILTER_PIXELATE,$this->Pixelate[1],true);
		} else {
			for ($y=0;$y<$this->size[1];$y+=$this->Pixelate[1]) {
				for ($x=0;$x<$this->size[0];$x+=$this->Pixelate[1]) {
					$pixel=ImageColorAt($this->im,$x,$y);
					imagefilledrectangle($this->im,$x,$y,$x+$this->Pixelate[1]-1,$y+$this->Pixelate[1]-1,$pixel);	
				}
			}
		}

	}

	/**
	 * Bilinear interpolation 
	 *
	 */	
	private function bilinear($xnew,$ynew) {
		
		$xf=floor($xnew);$xc=$xf+1;$fracx=$xnew-$xf;$fracx1=1-$fracx;
		$yf=floor($ynew);$yc=$yf+1;$fracy=$ynew-$yf;$fracy1=1-$fracy;
		$ff=$this->rgbpixel($xf,$yf);$cf=$this->rgbpixel($xc,$yf);
		$fc=$this->rgbpixel($xf,$yc);$cc=$this->rgbpixel($xc,$yc);
		$red=floor($fracy1*($fracx1*$ff['red']+$fracx*$cf['red'])+$fracy*($fracx1*$fc['red']+$fracx*$cc['red']));
		$green=floor($fracy1*($fracx1*$ff['green']+$fracx*$cf['green'])+$fracy*($fracx1*$fc['green']+$fracx*$cc['green']));
		$blue=floor($fracy1*($fracx1*$ff['blue']+$fracx*$cf['blue'])+$fracy*($fracx1*$fc['blue']+$fracx*$cc['blue']));
		return array('red' => $red,'green' => $green,'blue' => $blue,'alpha' => $cc['alpha']);
		
	}

	/**
	 * Apply twirl FX to image
	 *
	 */	
	private function twirlfx() {
		
		$rotationamount=$this->Twirlfx[1]/1000;
		$centerx=floor($this->size[0]/2);$centery=floor($this->size[1]/2);
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$truex=$x-$centerx;$truey=$y-$centery;
				$theta=atan2(($truey),($truex));
				$radius=sqrt($truex*$truex+$truey*$truey);
				if ($this->Twirlfx[2]==0) {
					$newx=$centerx+($radius*cos($theta+$rotationamount*$radius));
					$newy=$centery+($radius*sin($theta+$rotationamount*$radius));
				} else {
					$newx=$centerx-($radius*cos($theta+$rotationamount*$radius));
					$newy=$centery-($radius*sin($theta+$rotationamount*$radius));					
				}
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);

	}

	/**
	 * Apply ripple FX to image
	 *
	 */	
	private function ripplefx() {
		
		$wavex=((2*pi())/$this->size[0])*$this->Ripplefx[1];
		$wavey=((2*pi())/$this->size[1])*$this->Ripplefx[3];
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newx=$x+$this->Ripplefx[4]*sin($y*$wavey); 
				$newy=$y+$this->Ripplefx[2]*sin($x*$wavex);
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);

	}

	/**
	 * Apply lake FX to image
	 *
	 */	
	private function lakefx() {
		
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		$ystart=max($this->size[1]-floor($this->size[1]*($this->Lakefx[2]/100)),0);
		if ($ystart>0) {
		    imagecopy($this->newimage,$this->im,0,0,0,0,$this->size[0],$this->size[1]);
		}
		for ($y=$ystart;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newy=$y+3*pi()*(1/$this->size[1])*$y*sin(($this->size[1]*($this->Lakefx[1]/100)*($this->size[1]-$y))/$y); 
				$newpix=$this->bilinear($x,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);

	}

	/**
	 * Apply waterdrop FX to image
	 *
	 */	
	private function waterdropfx() {
		
		$centerx=floor($this->size[0]/2);$centery=floor($this->size[1]/2);
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$truex=$x-$centerx;$truey=$y-$centery;
				$distance=sqrt($truex*$truex+$truey*$truey);	
				$amount=$this->Waterdropfx[1]*sin($distance/$this->Waterdropfx[3]*2*pi());
				$amount=$amount*($this->Waterdropfx[2]-$distance)/$this->Waterdropfx[2];
				if ($distance!=0) {$amount=$amount*$this->Waterdropfx[3]/$distance;}
				$newx=$x+$truex*$amount;
				$newy=$y+$truey*$amount;
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		
	}

	/**
	 * Create a transparent image
	 *
	 */	
	private function maketransparent() {
		
		$red=hexdec(substr($this->Maketransparent[2],1,2));$green=hexdec(substr($this->Maketransparent[2],3,2));$blue=hexdec(substr($this->Maketransparent[2],5,2));
		if ($this->Maketransparent[3]!=0) {
			$transparentcolor=imagecolorallocate($this->thumb,$red,$green,$blue);
			$tolerance=sqrt(pow($this->Maketransparent[3],2)+pow($this->Maketransparent[3],2)+pow($this->Maketransparent[3],2));
			for ($y=0;$y<$this->thumby;$y++) {
				for ($x=0;$x<$this->thumbx;$x++) {
					$pixel=ImageColorAt($this->thumb,$x,$y);
					$redpix=($pixel >> 16 & 0xFF);$greenpix=($pixel >> 8 & 0xFF);$bluepix=($pixel & 0xFF);
					if (sqrt(pow($redpix-$red,2)+pow($greenpix-$green,2)+pow($bluepix-$blue,2))<$tolerance)
						imagesetpixel($this->thumb,$x,$y,$transparentcolor);	
				}
			}
		}
		$transparentcolor=imagecolorallocate($this->thumb,$red,$green,$blue);
		imagecolortransparent($this->thumb,$transparentcolor);	
		if ($this->Maketransparent[1]!=2) {
			if ($this->Maketransparent[1]==0) {$this->size[2]=3;} else {$this->size[2]=1;}
		}
	}
	
	/**
	 * Create a animated PNG image
	 *
	 * @param array $frames
	 * @param string $output
	 * @param string $delay
	 */	
	public function Create_apng($frames, $outputFilename, $delay) {
        
		$imageData = array();
		$IHDR = array();
		$sequenceNumber = 0;
		foreach ($frames as $frame) {
			if (file_exists($frame)) {
				$fh = fopen($frame,'rb');
				$chunkData = fread($fh, 8);                                                 
				$header = unpack("C1highbit/"."A3signature/". "C2lineendings/"."C1eof/"."C1eol", $chunkData);
				if (is_array($header) && $header['highbit'] == 0x89 && $header['signature'] == "PNG") {
					$IDAT='';
					while (!feof($fh)) {
						$chunkData = fread($fh, 8);
						$chunkDataHeader = unpack ("N1length/A4type", $chunkData);                    
						switch ($chunkDataHeader['type']) {
							case 'IHDR':                                                  
								if (count($IHDR) == 0) {
									$chunkData = fread($fh, $chunkDataHeader['length']);     
									$IHDR = unpack("N1width/"."N1height/". "C1bits/"."C1color/"."C1compression/"."C1prefilter/"."C1interlacing", $chunkData);
									fseek($fh, 4, SEEK_CUR);                                
								} else {
									fseek($fh, $chunkDataHeader['length'] + 4, SEEK_CUR);    
								}
								break;            
							case 'IDAT':                                                     
								$IDAT .= fread($fh, $chunkDataHeader['length']);     
								fseek($fh, 4, SEEK_CUR);                                     
								break;                      
							case 'IEND';                                                     
								break 2;                                                   
							default:
								fseek($fh, $chunkDataHeader['length'] + 4, SEEK_CUR);
								break;
						}                    
					}
					fclose($fh);
					$imageData[] = $IDAT;
				} else {
					fclose($fh);
				}
			}
		}
    		$pngHeader = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
		$IHDR_chunk = $this->create_chunk('IHDR', pack('NNCCCCC', $IHDR['width'], $IHDR['height'], $IHDR['bits'], $IHDR['color'], $IHDR['compression'], $IHDR['prefilter'], $IHDR['interlacing']));
		$acTL_chunk = $this->create_chunk('acTL', pack("NN", count($imageData), 0));
		$data = $this->create_fcTL($sequenceNumber, $IHDR['width'], $IHDR['height'], $delay);  
		$fcTL_chunk = $this->create_chunk('fcTL', $data);
		$sequenceNumber += 1;
		if (count($imageData) == 1) {$acTL_chunk = $fcTL_chunk = '';}
		$fh = fopen($outputFilename, 'w');
		foreach ($imageData as $key => $image) {
			if ($key == 0) {
				$firstFrame = $this->create_chunk('IDAT', $image);
				fwrite($fh, $pngHeader . $IHDR_chunk . $acTL_chunk . $fcTL_chunk . $firstFrame);
			} else {
				$data = $this->create_fcTL($sequenceNumber, $IHDR['width'], $IHDR['height'], $delay);  
				$fcTL_chunk = $this->create_chunk('fcTL', $data);
				$sequenceNumber += 1;
				$data = pack("N", $sequenceNumber);
				$data .= $image; 
				$fdAT_chunk = $this->create_chunk('fdAT', $data);
				$sequenceNumber += 1;            
				fwrite($fh, $fcTL_chunk . $fdAT_chunk);
			}
		}
		fwrite($fh, $this->create_chunk('IEND'));
		fclose($fh);
    
	}

	/**
	 * Create a PNG binary chunk
	 *
	 * @param array $type
	 * @param string $data
	 */
	private function create_chunk($type, $data = '') {

		$chunk = pack("N", strlen($data)) . $type . $data . pack("N", crc32($type . $data));        
		return $chunk;
		
	}

	/**
	 * Create a PNG fcTL binary chunk
	 *
	 * @param array $frameNumber
	 * @param string $width
	 * @param string $height
	 * @param string $delay
	 */
	private function create_fcTL($frameNumber, $width, $height, $delay) {

		$fcTL = array();
		$fcTL['sequence_number'] = $frameNumber;
		$fcTL['width'] = $width;
		$fcTL['height'] = $height;
		$fcTL['x_offset'] = 0;
		$fcTL['y_offset'] = 0;
		$fcTL['delay_num'] = $delay;
		$fcTL['delay_den'] = 1000;
		$fcTL['dispose_op'] = 0;
		$fcTL['blend_op'] = 0;
		$data = pack("NNNNN", $fcTL['sequence_number'], $fcTL['width'], $fcTL['height'], $fcTL['x_offset'], $fcTL['y_offset']);
		$data .= pack("nn", $fcTL['delay_num'], $fcTL['delay_den']);
		$data .= pack("cc", $fcTL['dispose_op'], $fcTL['blend_op']);
		return $data;     
		
	}
	
	/**
	 * Create image from canvas
	 *
	 * @param int width
	 * @param int height
	 * @param int IMAGETYPE_XXX
	 * @param string background color
	 * @param boolean transparent	 
	 */	
	public function Createcanvas($width, $height, $filetype, $bgcolor, $transparent) {
        
		$this->im=imagecreatetruecolor($width,$height);
		$this->size=array($width,$height,$filetype);
		$color=imagecolorallocate($this->im,hexdec(substr($bgcolor,1,2)),hexdec(substr($bgcolor,3,2)),hexdec(substr($bgcolor,5,2)));
		imagefilledrectangle($this->im,0,0,$width,$height,$color);
		if ($transparent) {
			$this->Keeptransparency=true;
			imagecolortransparent($this->im,$color);
		}
		
	}	
	
	/**
	 * Output the thumbnail as base64 encoded text
	 *
	 * @param string filename
	 */	
	public function Createbase64($filename="unknown") {

		$this->image=$filename;
		$this->thumbmaker();
		ob_start();
		switch($this->size[2]) {
			case 1:
				$encoding='data:image/gif;base64,';
				imagegif($this->thumb);
				break;
			case 2:
				$encoding='data:image/jpeg;base64,';
				imagejpeg($this->thumb,'',$this->Quality);
				break;
			case 3:
				$encoding='data:image/png;base64,';
				imagepng($this->thumb);
				break;
		}
		$imagecode=ob_get_contents();
		ob_end_clean();
		$encoded=$encoding . chunk_split(base64_encode($imagecode)) . '"'; 
		return $encoded;
		
	}
	
	/**
	 * Apply polaroid look to original image
	 *
	 */	
	private function polaroid() {
	
		$originalarray=$this->Cropimage;
		if ($this->size[0]>$this->size[1]) {
			$cropwidth=floor(($this->size[0]-floor(($this->size[1]/1.05)))/2);
			$this->Cropimage=array(1,1,$cropwidth,$cropwidth,0,-1*floor(0.16*$this->size[1]));
			$this->cropimage();
			$this->Framewidth=floor(0.05*($this->size[1]-2*$cropwidth));
		} else {
			$cropheight=floor(($this->size[1]-floor(($this->size[0]/1.05)))/2);
			$bottom=-1*floor(0.16*$this->size[1]);
			$this->Cropimage=array(1,1,0,0,$cropheight,$cropheight);
			$this->cropimage();
			$this->Cropimage=array(1,1,0,0,0,$bottom);
			$this->cropimage();
			$this->Framewidth=floor(0.05*$this->size[0]);
		}
		$this->Cropimage=$originalarray;
		if ($this->Polaroidtext!='' && $this->Polaroidfonttype!='') {
		  $dimensions=imagettfbbox($this->Polaroidfontsize,0,$this->Polaroidfonttype,$this->Polaroidtext);
			$widthx=$dimensions[2];
			$heighty=$dimensions[5];
			$y=$this->size[1]-floor($this->size[1]*0.08)-$heighty;
			$x=floor(($this->size[0]-$widthx)/2);
			imagettftext($this->im,$this->Polaroidfontsize,0,$x,$y,imagecolorallocate($this->im,hexdec(substr($this->Polaroidtextcolor,1,2)),hexdec(substr($this->Polaroidtextcolor,3,2)),hexdec(substr($this->Polaroidtextcolor,5,2))),$this->Polaroidfonttype,$this->Polaroidtext);		
		}
		
	}

	/**
	 * Apply displacement map
	 *
	 */	
	private function displace() {
		
		if (file_exists($this->Displacementmap[1])) {
			$size=GetImageSize($this->Displacementmap[1]);
			switch($size[2]) {
				case 1:
					if (imagetypes() & IMG_GIF) {$map=imagecreatefromgif($this->Displacementmap[1]);} else {$map=imagecreatetruecolor(100,100);}
					break;
				case 2:
					if (imagetypes() & IMG_JPG) {$map=imagecreatefromjpeg($this->Displacementmap[1]);} else {$map=imagecreatetruecolor(100,100);}
					break;
				case 3:
					if (imagetypes() & IMG_PNG) {$map=imagecreatefrompng($this->Displacementmap[1]);} else {$map=imagecreatetruecolor(100,100);}
					break;
				default:
					$map=imagecreatetruecolor(100,100);
			}
		} else {
			$map=imagecreatetruecolor(100,100);
		}
		$mapxmax=imagesx($map);$mapymax=imagesy($map);
		$this->newimage=imagecreatetruecolor($this->size[0],$this->size[1]);
		if ($this->Displacementmap[2]==0) {
			$maptmp=imagecreatetruecolor($this->size[0],$this->size[1]);
			imagecopyresampled($maptmp,$map,0,0,0,0,$this->size[0],$this->size[1],$mapxmax,$mapymax);
			imagedestroy($map);
			$map=imagecreatetruecolor($this->size[0],$this->size[1]);
			imagecopy($map,$maptmp,0,0,0,0,$this->size[0],$this->size[1]);
			imagedestroy($maptmp);
			$mapxmax=$this->size[0];
			$mapymax=$this->size[1];
			$mapx=$this->Displacementmap[3];
			$mapy=$this->Displacementmap[4];
		} else {
			$mapx=$this->Displacementmap[3];
			$mapy=$this->Displacementmap[4];		
		}
		for ($y=0;$y<$this->size[1];$y++) {
			for ($x=0;$x<$this->size[0];$x++) {
				$newx=$x;$newy=$y;
				if ($x>=$mapx && $y>=$mapy) {
					if ($x<$mapxmax && $y<$mapymax) {
						$pixelmap=ImageColorAt($map,$x-$mapx,$y-$mapy);
						$redmap=1+$pixelmap >> 16 & 0xFF;
						$greenmap=1+$pixelmap >> 8 & 0xFF;
						$newx=$x+(($redmap-128)*$this->Displacementmap[5])/256;
						$newy=$y+(($greenmap-128)*$this->Displacementmap[6])/256;
					}
				}
				$newpix=$this->bilinear($newx,$newy);
				imagesetpixel($this->newimage,$x,$y,imagecolorallocatealpha($this->newimage,$newpix['red'],$newpix['green'],$newpix['blue'],$newpix['alpha']));	
			}
		}
		imagecopy($this->im,$this->newimage,0,0,0,0,$this->size[0],$this->size[1]);
		imagedestroy($this->newimage);
		imagedestroy($map);
		
	}

	/**
	 * Apply displacement map to thumbnail
	 *
	 */	
	private function displacethumb() {
		
		if (is_resource($this->thumb)) {
			$temparray=$this->Displacementmap;
			imagedestroy($this->im);
			$this->im=imagecreatetruecolor($this->thumbx,$this->thumby);
			imagecopy($this->im,$this->thumb,0,0,0,0,$this->thumbx,$this->thumby);
			$this->size[0]=$this->thumbx;$this->size[1]=$this->thumby;
			$this->Displacementmap=$this->Displacementmapthumb;
			$this->displace();
			$this->Displacementmap=$temparray;
			imagecopy($this->thumb,$this->im,0,0,0,0,$this->thumbx,$this->thumby);
		}		
		
	}	
	
}

?>
