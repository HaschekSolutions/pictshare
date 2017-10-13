<?php

namespace App\Transformers;

/**
 * Class Filter
 * @package App\Transformers
 */
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
     * Filter constructor.
     *
     * @param resource $image GD image resource
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

    /**
     * @return Filter
     */
    public function bubbles()
    {
        $dest = imagecreatefromjpeg($this->assetDirectory . "pattern4.jpg");

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($dest);
        $y2 = imagesy($dest);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $dest, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 20);
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 40);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -10);

        return $this;
    }

    /**
     * @return Filter
     */
    public function colorise()
    {
        $dest = imagecreatefromjpeg($this->assetDirectory . "pattern5.jpg");

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($dest);
        $y2 = imagesy($dest);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $dest, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 40);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -25);

        return $this;
    }

    /**
     * @return Filter
     */
    public function sepia()
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0);

        return $this;
    }

    /**
     * @return Filter
     */
    public function sharpen()
    {
        $gaussian = [
            [1.0, 1.0, 1.0],
            [1.0, -7.0, 1.0],
            [1.0, 1.0, 1.0]
        ];
        imageconvolution($this->image, $gaussian, 1, 4);

        return $this;
    }

    /**
     * @return Filter
     */
    public function emboss()
    {
        $gaussian = [
            [-2.0, -1.0, 0.0],
            [-1.0, 1.0, 1.0],
            [0.0, 1.0, 2.0]
        ];

        imageconvolution($this->image, $gaussian, 1, 5);

        return $this;
    }

    /**
     * @return Filter
     */
    public function cool()
    {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        imagefilter($this->image, IMG_FILTER_CONTRAST, -50);

        return $this;
    }

    /**
     * @return Filter
     */
    public function old2()
    {
        $dest = imagecreatefromjpeg($this->assetDirectory . "pattern1.jpg");

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($dest);
        $y2 = imagesy($dest);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $dest, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 40);

        return $this;
    }

    /**
     * @return Filter
     */
    public function old3()
    {
        imagefilter($this->image, IMG_FILTER_CONTRAST, -30);

        $dest = imagecreatefromjpeg($this->assetDirectory . "pattern3.jpg");

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($dest);
        $y2 = imagesy($dest);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $dest, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 50);

        return $this;
    }

    /**
     * @return Filter
     */
    public function old()
    {
        $dest = imagecreatefromjpeg($this->assetDirectory . "bg1.jpg");

        $x = imagesx($this->image);
        $y = imagesy($this->image);

        $x2 = imagesx($dest);
        $y2 = imagesy($dest);

        $thumb = imagecreatetruecolor($x, $y);
        imagecopyresampled($thumb, $dest, 0, 0, 0, 0, $x, $y, $x2, $y2);

        imagecopymerge($this->image, $thumb, 0, 0, 0, 0, $x, $y, 30);

        return $this;
    }

    /**
     * @return Filter
     */
    public function light()
    {
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 10);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0, 10);

        return $this;
    }

    /**
     * @return Filter
     */
    public function aqua()
    {
        imagefilter($this->image, IMG_FILTER_COLORIZE, 0, 70, 0, 30);

        return $this;
    }

    /**
     * @return Filter
     */
    public function fuzzy()
    {
        $gaussian = [
            [1.0, 1.0, 1.0],
            [1.0, 1.0, 1.0],
            [1.0, 1.0, 1.0]
        ];

        imageconvolution($this->image, $gaussian, 9, 20);

        return $this;
    }

    /**
     * @return Filter
     */
    public function boost()
    {
        imagefilter($this->image, IMG_FILTER_CONTRAST, -35);
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 10);

        return $this;
    }

    /**
     * @return Filter
     */
    public function gray()
    {
        imagefilter($this->image, IMG_FILTER_CONTRAST, -60);
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);

        return $this;
    }
}
