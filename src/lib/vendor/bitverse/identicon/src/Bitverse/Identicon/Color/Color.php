<?php

namespace Bitverse\Identicon\Color;

class Color
{
    /**
     * @var string
     */
    private $red;

    /**
     * @var string
     */
    private $green;

    /**
     * @var string
     */
    private $blue;

    /**
     * Parses a hex string to create a Color object.
     *
     * @throws WrongColorFormatException Thrown if the given string is in the wrong format.
     *
     * @param string $color
     * @return Color
     */
    public static function parseHex($color)
    {
        if (!preg_match('/^#[A-Fa-f0-9]{6}$/', $color)) {
            throw new WrongColorFormatException($color);
        }

        return new Color(
            hexdec(substr($color, 1, 2)),
            hexdec(substr($color, 3, 2)),
            hexdec(substr($color, 5, 2))
        );
    }

    /**
     * @param integer $red
     * @param integer $green
     * @param integer $blue
     */
    public function __construct($red, $green, $blue)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'rgb(' . implode(', ', [$this->red, $this->green, $this->blue]) . ')';
    }
}
