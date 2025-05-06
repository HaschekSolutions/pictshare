<?php

namespace Bitverse\Identicon\SVG;

class Path extends SvgNode
{
    /**
     * @var string[]
     */
    private $path;

    /**
     * Starts a new path at the given point.
     *
     * @param int $x X-coordinate
     * @param int $y Y-coordinate
     *
     * @return Path
     */
    public function __construct($x, $y)
    {
        $this->path = [sprintf('M %d,%d', $x, $y)];
    }

    public function lineTo($x, $y, $relative = false)
    {
        $this->path[] = sprintf(
            '%s %d,%d',
            $relative ? 'l' : 'L',
            $x,
            $y
        );

        return $this;
    }

    /**
     * [arcTo description]
     * @param integer $x
     * @param integer $y
     * @param integer $xRadius
     * @param integer $yRadius
     * @param integer $xRotation
     * @param boolean $largeArc
     * @param boolean $sweepClockwise
     * @param boolean $relative
     *
     * @return Path
     */
    public function arcTo(
        $x,
        $y,
        $xRadius,
        $yRadius,
        $xRotation,
        $largeArc,
        $sweepClockwise,
        $relative = false
    ) {
        $this->path[] = sprintf(
            '%s %d,%d %d %d %d %d,%d',
            $relative ? 'a' : 'A',
            $xRadius,
            $yRadius,
            $xRotation,
            $largeArc ? 1 : 0,
            $sweepClockwise ? 1 : 0,
            $x,
            $y
        );

        return $this;
    }

    public function __toString()
    {
        return sprintf(
            '<path fill="%s" stroke="%s" stroke-width="%s" d="%s" />',
            (string) $this->getFillColor(),
            (string) $this->getStrokeColor(),
            $this->getStrokeWidth(),
            implode(' ', $this->path)
        );
    }
}
