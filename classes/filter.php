<?php

class Filter
{
    /**
     * @var resource
     */
    private $image;

    /**
     * Directory for image assets.
     *
     * @var string
     */
    private $assetDirectory;

    /**
     * run constructor
     *
     * @param resource &$image GD image resource
     */
    public function __construct(&$image)
    {
        $this->image = $image;

        $this->assetDirectory = dirname(dirname(dirname(__FILE__))) . '/assets/';
    }

    /**
     * Get the current image resource
     *
     * @return resource
     */
    public function getImage()
    {
        return $this->image;
    }

    public function bubbles()
    {
        $destination = imagecreatefromjpeg($this->assetDirectory . 'pattern4.jpg');

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($destination);
        $y2 = imagesy($destination);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $destination, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 20);
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 40);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -10);

        return $this;
    }

    public function colorise()
    {
        $destination = imagecreatefromjpeg($this->assetDirectory . 'pattern5.jpg');

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($destination);
        $y2 = imagesy($destination);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $destination, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 40);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -25);

        return $this;
    }

    public function sepia()
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0);

        return $this;
    }

    public function sharpen()
    {
        $gaussian = array(
                array(1.0, 1.0, 1.0),
                array(1.0, -7.0, 1.0),
                array(1.0, 1.0, 1.0)
        );
        imageconvolution($this->image, $gaussian, 1, 4);

        return $this;
    }

    public function emboss()
    {
        $gaussian = array(
                array(-2.0, -1.0, 0.0),
                array(-1.0, 1.0, 1.0),
                array(0.0, 1.0, 2.0)
        );

        imageconvolution($this->image, $gaussian, 1, 5);

        return $this;
    }

    public function cool()
    {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -50);

        return $this;
    }

    public function old2()
    {
        $destination = imagecreatefromjpeg($this->assetDirectory . 'pattern1.jpg');

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($destination);
        $y2 = imagesy($destination);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $destination, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 40);

        return $this;
    }

    public function old3()
    {
        imagefilter($this->image, IMG_FILTER_CONTRAST, -30);

        $destination = imagecreatefromjpeg($this->assetDirectory . 'pattern3.jpg');

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($destination);
        $y2 = imagesy($destination);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $destination, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 50);

        return $this;
    }

    public function old()
    {
        $destination = imagecreatefromjpeg($this->assetDirectory . 'bg1.jpg');

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($destination);
        $y2 = imagesy($destination);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $destination, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 30);

        return $this;
    }

    public function light()
    {
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 10);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0, 10);

        return $this;
    }

    public function aqua()
    {
        imagefilter($this->image, IMG_FILTER_COLORIZE, 0, 70, 0, 30);

        return $this;
    }

    public function fuzzy()
    {
        $gaussian = array(
                array(1.0, 1.0, 1.0),
                array(1.0, 1.0, 1.0),
                array(1.0, 1.0, 1.0)
        );

        imageconvolution($this->image, $gaussian, 9, 20);

        return $this;
    }

    public function boost()
    {
        imagefilter($this->image, IMG_FILTER_CONTRAST, -35);
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 10);

        return $this;
    }

    public function gray()
    {
        imagefilter($this->image, IMG_FILTER_CONTRAST, -60);
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);

        return $this;
    }
}
