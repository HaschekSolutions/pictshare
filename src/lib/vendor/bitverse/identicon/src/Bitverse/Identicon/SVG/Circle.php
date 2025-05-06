<?php

namespace Bitverse\Identicon\SVG;

class Circle extends SvgNode
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
    private $radius;

    /**
     * @param integer $x
     * @param integer $y
     * @param integer $radius
     */
    public function __construct($x, $y, $radius)
    {
        $this->x = $x;
        $this->y = $y;
        $this->radius = $radius;
    }

    public function __toString()
    {
        return sprintf(
            '<circle cx="%d" cy="%d" r="%d" fill="%s" stroke="%s" stroke-width="%d" />',
            $this->x,
            $this->y,
            $this->radius,
            (string) $this->getFillColor(),
            (string) $this->getStrokeColor(),
            $this->getStrokeWidth()
        );
    }
}
