<?php

namespace Bitverse\Identicon\SVG;

class Rectangle extends SvgNode
{
    /**
     * @var integer
     */
    private $x;

    /**
     * @var integer
     */
    private $y;

    /**
     * @var integer
     */
    private $width;

    /**
     * @var integer
     */
    private $height;

    /**
     * @param integer $x
     * @param integer $y
     * @param integer $width
     * @param integer $height
     */
    public function __construct($x, $y, $width, $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    public function __toString()
    {
        return sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" ' .
                'fill="%s" stroke="%s" stroke-width="%d" />',
            $this->x,
            $this->y,
            $this->width,
            $this->height,
            (string) $this->getFillColor(),
            (string) $this->getStrokeColor(),
            $this->getStrokeWidth()
        );
    }
}
