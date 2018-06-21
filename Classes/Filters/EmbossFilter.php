<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class EmbossFilter extends AbstractFilter
{
    const DIVISOR_KEY = 'divisor';
    const OFFSET_KEY  = 'offset';

    const DIVISOR_DEFAULT = 1;
    const OFFSET_DEFAULT  = 5;

    const GAUSSIAN_MAP = [
        [-2.0, -1.0, 0.0],
        [-1.0,  1.0, 1.0],
        [ 0.0,  1.0, 2.0],
    ];

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        imageconvolution(
            $this->image,
            self::GAUSSIAN_MAP,
            $this->getSettingsValue(self::DIVISOR_KEY),
            $this->getSettingsValue(self::OFFSET_KEY)
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::DIVISOR_KEY => self::DIVISOR_DEFAULT,
            self::OFFSET_KEY  => self::OFFSET_DEFAULT,
        ];
    }
}
