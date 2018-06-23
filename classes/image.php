<?php

use PictShare\Classes\FilterFactory;

class Image
{
    public function rotate(&$im, $direction)
    {
        switch ($direction) {
            case 'upside':
                $angle = 180;
                break;
            case 'left':
                $angle = 90;
                break;
            case 'right':
                $angle = -90;
                break;
            default:
                $angle = 0;
                break;
        }

        $im = imagerotate($im, $angle, 0);
    }

    public function forceResize(&$img, $size)
    {
        $pm = new PictshareModel();

        $sd = $pm->sizeStringToWidthHeight($size);
        $maxWidth  = $sd['width'];
        $maxHeight = $sd['height'];

        $width = imagesx($img);
        $height = imagesy($img);

        $maxWidth = ($maxWidth > $width ? $width : $maxWidth);
        $maxHeight = ($maxHeight > $height ? $height : $maxHeight);


        $dst_img = imagecreatetruecolor($maxWidth, $maxHeight);
        $src_img = $img;

        $palsize = imagecolorstotal($img);
        for ($i = 0; $i < $palsize; $i++) {
            $colors = imagecolorsforindex($img, $i);
            imagecolorallocate($dst_img, $colors['red'], $colors['green'], $colors['blue']);
        }

        imagefill($dst_img, 0, 0, IMG_COLOR_TRANSPARENT);
        imagesavealpha($dst_img, true);
        imagealphablending($dst_img, true);

        $width_new = $height * $maxWidth / $maxHeight;
        $height_new = $width * $maxHeight / $maxWidth;
        // If the new width is greater than the actual width of the image,
        // then the height is too large and the rest cut off, or vice versa.
        if ($width_new > $width) {
            //cut point by height
            $h_point = (($height - $height_new) / 2);
            //copy image
            imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $maxWidth, $maxHeight, $width, $height_new);
        } else {
            //cut point by width
            $w_point = (($width - $width_new) / 2);
            imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $maxWidth, $maxHeight, $width_new, $height);
        }

        $img = $dst_img;
    }

    /**
     * From: https://stackoverflow.com/questions/4590441/php-thumbnail-image-resizing-with-proportions
     *
     * @param $img
     * @param int $size
     */
    public function resize(&$img, $size)
    {
        $pm = new PictshareModel();

        $sd = $pm->sizeStringToWidthHeight($size);
        $maxWidth  = $sd['width'];
        $maxHeight = $sd['height'];

        $width = imagesx($img);
        $height = imagesy($img);

        if (!ALLOW_BLOATING) {
            if ($maxWidth > $width) {
                $maxWidth = $width;
            }
            if ($maxHeight > $height) {
                $maxHeight = $height;
            }
        }

        if ($height > $width) {
            $ratio = $maxHeight / $height;
            $newHeight = $maxHeight;
            $newWidth = $width * $ratio;
        } else {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = $height * $ratio;
        }

        $newImg = imagecreatetruecolor($newWidth, $newHeight);

        $palSize = imagecolorstotal($img);
        for ($i = 0; $i < $palSize; $i++) {
            $colors = imagecolorsforindex($img, $i);
            imagecolorallocate($newImg, $colors['red'], $colors['green'], $colors['blue']);
        }

        imagefill($newImg, 0, 0, IMG_COLOR_TRANSPARENT);
        imagesavealpha($newImg, true);
        imagealphablending($newImg, true);

        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $img = $newImg;
    }

    /**
     * @param $im
     * @param array $vars
     *
     * @return resource GD image resource
     */
    public function filter(&$im, $vars)
    {
        foreach ($vars as $var) {
            $filterName  = $var;
            $filterValue = null;
            $params      = [];

            if (strpos($var, '_')) {
                list($filterName, $filterValue) = explode('_', $var);
            }

            if ($filterValue !== null) {
                $params = [
                    'brightness' => $filterValue,
                    'blur'       => $filterValue,
                    'pixelate'   => $filterValue,
                    'smooth'     => $filterValue,
                ];
            }

            $im = FilterFactory::getFilter($filterName)
                ->setImage($im)
                ->setSettings($params)
                ->apply()
                ->getImage();
        }

        return $im;
    }
}
