<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class OldThreeFilter extends AbstractFilter
{
    const CONTRAST_KEY = 'contrast';
    const PERCENT_KEY  = 'percent';
    const FILENAME     = 'pattern3.jpg';

    const CONTRAST_DEFAULT = -30;
    const PERCENT_DEFAULT  = 50;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        imagefilter(
            $this->image,
            IMG_FILTER_CONTRAST,
            $this->getSettingsValue(self::CONTRAST_KEY)
        );

        $destination = imagecreatefromjpeg($this->assetDirectory . self::FILENAME);
        $x           = imagesx($this->image);
        $y           = imagesy($this->image);
        $x2          = imagesx($destination);
        $y2          = imagesy($destination);
        $thumb       = imagecreatetruecolor($x, $y);

        imagecopyresampled(
            $thumb,
            $destination,
            0,
            0,
            0,
            0,
            $x,
            $y,
            $x2,
            $y2
        );

        imagecopymerge(
            $this->image,
            $thumb,
            0,
            0,
            0,
            0,
            $x,
            $y,
            $this->getSettingsValue(self::PERCENT_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::CONTRAST_KEY => self::CONTRAST_DEFAULT,
            self::PERCENT_KEY  => self::PERCENT_DEFAULT,
        ];
    }
}
