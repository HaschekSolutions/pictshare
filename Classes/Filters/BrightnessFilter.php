<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class BrightnessFilter extends AbstractFilter
{
    const BRIGHTNESS_KEY = 'brightness';

    const BRIGHTNESS_DEFAULT = 50;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
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
        ];
    }
}
