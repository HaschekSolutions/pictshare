<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class CoolFilter extends AbstractFilter
{
    const CONTRAST_KEY = 'contract';

    const CONTRAST_DEFAULT = -50;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        imagefilter(
            $this->image,
            IMG_FILTER_MEAN_REMOVAL
        );

        imagefilter(
            $this->image,
            IMG_FILTER_CONTRAST,
            $this->getSettingsValue(self::CONTRAST_KEY)
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
