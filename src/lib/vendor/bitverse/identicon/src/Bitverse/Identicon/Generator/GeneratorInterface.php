<?php

namespace Bitverse\Identicon\Generator;

use Bitverse\Identicon\Color\Color;

interface GeneratorInterface
{
    /**
     * Sets the background color for the generated icons.
     *
     * @param Color|string $color
     */
    public function setBackgroundColor($color);

    /**
     * Generates a unique icon
     *
     * @param string $hash The hash to generate the icon from.
     *
     * @return string Svg output.
     */
    public function generate($hash);
}
