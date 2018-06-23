<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

class RotateFilter extends AbstractFilter
{
    const ANGLE_KEY = 'angle';

    const ANGLE_DEFAULT = 0;

    /**
     * @inheritdoc
     */
    final public function apply(): FilterInterface
    {
        $angle = $this->getSettingsValue(self::ANGLE_KEY);

        if ($angle === 0) {
            return $this;
        }

        $this->image = imagerotate(
            $this->image,
            $angle,
            0
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    final public function getDefaults(): array
    {
        return [
            self::ANGLE_KEY => self::ANGLE_DEFAULT,
        ];
    }
}
