<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class BoostFilter extends AbstractFilter
{
    const BRIGHTNESS_KEY = 'brightness';
    const CONTRAST_KEY   = 'contrast';

    const BRIGHTNESS_DEFAULT = 10;
    const CONTRAST_DEFAULT   = -35;

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

        imagefilter(
            $this->image,
            IMG_FILTER_BRIGHTNESS,
            $this->getSettingsValue(self::BRIGHTNESS_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::BRIGHTNESS_KEY => self::BRIGHTNESS_DEFAULT,
            self::CONTRAST_KEY   => self::CONTRAST_DEFAULT,
        ];
    }
}
