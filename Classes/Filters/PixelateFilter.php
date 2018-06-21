<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class PixelateFilter extends AbstractFilter
{
    const PIXELATION_KEY = 'pixelation';

    const PIXELATION_DEFAULT = 4;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        imagefilter(
            $this->image,
            IMG_FILTER_PIXELATE,
            $this->getSettingsValue(self::PIXELATION_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::PIXELATION_KEY => self::PIXELATION_DEFAULT,
        ];
    }
}
