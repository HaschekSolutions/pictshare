<?php

namespace Bitverse\Identicon\Color;

class WrongColorFormatException extends \Exception
{
    /**
     * @var string
     */
    private $color;

    /**
     * @param string $color
     */
    public function __construct($color)
    {
        $this->color = $color;
    }

    public function getMessage()
    {
        return sprintf('"%s" is not a valid color format!', $this->color);
    }
}
