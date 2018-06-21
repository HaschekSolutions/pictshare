<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class SepiaFilter extends AbstractFilter
{
    const RED_KEY   = 'red';
    const GREEN_KEY = 'green';
    const BLUE_KEY  = 'blue';

    const RED_DEFAULT   = 100;
    const GREEN_DEFAULT = 50;
    const BLUE_DEFAULT  = 0;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        imagefilter(
            $this->image,
            IMG_FILTER_GRAYSCALE
        );

        imagefilter(
            $this->image,
            IMG_FILTER_COLORIZE,
            $this->getSettingsValue(self::RED_KEY),
            $this->getSettingsValue(self::GREEN_KEY),
            $this->getSettingsValue(self::BLUE_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::RED_KEY   => self::RED_DEFAULT,
            self::GREEN_KEY => self::GREEN_DEFAULT,
            self::BLUE_KEY  => self::BLUE_DEFAULT,
        ];
    }
}
