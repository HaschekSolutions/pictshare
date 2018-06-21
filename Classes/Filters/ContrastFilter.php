<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class ContrastFilter extends AbstractFilter
{
    const CONTRAST_KEY = 'contrast';

    const CONTRAST_DEFAULT = 50;

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
