<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class SmoothFilter extends AbstractFilter
{
    const SMOOTHNESS_KEY = 'smoothness';

    const SMOOTHNESS_DEFAULT = 50;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        imagefilter(
            $this->image,
            IMG_FILTER_SMOOTH,
            $this->getSettingsValue(self::SMOOTHNESS_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::SMOOTHNESS_KEY => self::SMOOTHNESS_DEFAULT,
        ];
    }
}
