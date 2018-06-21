<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class LightFilter extends AbstractFilter
{
    const RED_KEY        = 'red';
    const GREEN_KEY      = 'green';
    const BLUE_KEY       = 'blue';
    const ALPHA_KEY      = 'alpha';
    const BRIGHTNESS_KEY = 'brightness';

    const RED_DEFAULT        = 0;
    const GREEN_DEFAULT      = 70;
    const BLUE_DEFAULT       = 0;
    const ALPHA_DEFAULT      = 30;
    const BRIGHTNESS_DEFAULT = 10;

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

        imagefilter(
            $this->image,
            IMG_FILTER_COLORIZE,
            $this->getSettingsValue(self::RED_KEY),
            $this->getSettingsValue(self::GREEN_KEY),
            $this->getSettingsValue(self::BLUE_KEY),
            $this->getSettingsValue(self::ALPHA_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::RED_KEY        => self::RED_DEFAULT,
            self::GREEN_KEY      => self::GREEN_DEFAULT,
            self::BLUE_KEY       => self::BLUE_DEFAULT,
            self::ALPHA_KEY      => self::ALPHA_DEFAULT,
            self::BRIGHTNESS_KEY => self::BRIGHTNESS_DEFAULT,
        ];
    }
}
