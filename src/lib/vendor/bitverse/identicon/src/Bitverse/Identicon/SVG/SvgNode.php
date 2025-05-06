<?php

namespace Bitverse\Identicon\SVG;

use Bitverse\Identicon\Color\Color;

abstract class SvgNode
{
    /**
     * @var Color
     */
    private $fillColor;

    /**
     * @var Color
     */
    private $strokeColor;

    /**
     * @var integer
     */
    private $strokeWidth;

    /**
     * @param Color $color
     * @return SvgNode
     */
    public function setFillColor(Color $color)
    {
        $this->fillColor = $color;
        return $this;
    }

    /**
     * @return Color
     */
    public function getFillColor()
    {
        return $this->fillColor;
    }

    /**
     * @param Color $color
     * @return SvgNode
     */
    public function setStrokeColor(Color $color)
    {
        $this->strokeColor = $color;
        return $this;
    }

    /**
     * @return Color
     */
    public function getStrokeColor()
    {
        return $this->strokeColor;
    }

    /**
     * @param integer $width
     * @return SvgNode
     */
    public function setStrokeWidth($width)
    {
        $this->strokeWidth = $width;
        return $this;
    }

    /**
     * @return integer
     */
    public function getStrokeWidth()
    {
        return $this->strokeWidth;
    }
}
