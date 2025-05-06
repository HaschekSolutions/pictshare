<?php

namespace Bitverse\Identicon\SVG;

class Svg extends SvgNode
{
    private $width;

    private $height;

    private $children = [];

    /**
     * @param integer $width
     * @param integer $height
     */
    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @param SvgNode $node
     *
     * @return Svg
     */
    public function addChild(SvgNode $node = null)
    {
        $this->children[] = $node;
        return $this;
    }

    public function __toString()
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ' .
                'width="%d" height="%d">%s</svg>',
            $this->width,
            $this->height,
            implode('', array_map(function ($node) {
                return (string) $node;
            }, $this->children))
        );
    }
}
