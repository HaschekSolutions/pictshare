<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class OldTwoFilter extends AbstractFilter
{
    const PERCENT_KEY = 'percent';
    const FILENAME    = 'pattern1.jpg';

    const PERCENT_DEFAULT = 40;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
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
            self::PERCENT_KEY => self::PERCENT_DEFAULT,
        ];
    }
}
