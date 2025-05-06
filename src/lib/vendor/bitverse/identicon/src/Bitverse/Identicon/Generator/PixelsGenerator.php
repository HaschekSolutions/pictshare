<?php

namespace Bitverse\Identicon\Generator;

use Bitverse\Identicon\Color\Color;
use Bitverse\Identicon\SVG\Svg;
use Bitverse\Identicon\SVG\Rectangle;

class PixelsGenerator extends BaseGenerator
{
    /**
     * {@inheritDoc}
     */
    public function generate($hash)
    {
        $svg = (new Svg(480, 480))->addChild($this->getBackground());

        for ($i=0; $i<5; ++$i) {
            for ($j=0; $j<5; ++$j) {
                if ($this->showPixel($i, $j, $hash)) {
                    $svg->addChild($this->getPixel($i, $j, $this->getColor($hash)));
                }
            }
        }

        return (string) $svg;

    }

    /**
     * Returns the background rectangle.
     *
     * @return SvgNode
     */
    private function getBackground()
    {
        return (new Rectangle(0, 0, 480, 480))
            ->setFillColor($this->getBackgroundColor())
            ->setStrokeWidth(0);
    }

    /**
     * Returns a pixel drawn accordingly to the passed parameters.
     *
     * @param integer $x
     * @param integer $y
     * @param Color $color
     *
     * @return SvgNode
     */
    private function getPixel($x, $y, Color $color)
    {
        return (new Rectangle($x * 80 + 40, $y * 80 + 40, 80, 80))
            ->setFillColor($color)
            ->setStrokeWidth(0);
    }

    /**
     * Determines whether a pixel from the 5x5 grid should be visible.
     *
     * @param integer $x
     * @param integer $y
     * @param string $hash
     *
     * @return boolean
     */
    private function showPixel($x, $y, $hash)
    {
        return hexdec(substr($hash, 6 + abs(2-$x) * 5 + $y, 1)) % 2 === 0;
    }
}
