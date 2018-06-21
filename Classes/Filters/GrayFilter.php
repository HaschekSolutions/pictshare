<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class GrayFilter extends AbstractFilter
{
    const CONTRAST_KEY = 'contract';

    const CONTRAST_DEFAULT = -60;

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
            IMG_FILTER_GRAYSCALE
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
        ];
    }
}
