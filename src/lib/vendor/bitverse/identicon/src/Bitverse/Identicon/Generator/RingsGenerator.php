<?php

namespace Bitverse\Identicon\Generator;

use Bitverse\Identicon\Color\Color;
use Bitverse\Identicon\SVG\Svg;
use Bitverse\Identicon\SVG\Circle;
use Bitverse\Identicon\SVG\Path;

class RingsGenerator extends BaseGenerator
{
    /**
     * @var integer
     */
    private $sideLength = 1000;

    /**
     * {@inheritDoc}
     */
    public function generate($hash)
    {
        $svg = (new Svg($this->sideLength, $this->sideLength))->addChild(
            $this->getBackground()
        );

        if ($this->showCenter($hash)) {
            $svg->addChild($this->getCenter($this->getColor($hash)));
        }

        for ($i=1; $i<4; ++$i) {
            $svg->addChild(
                $this->getArc(
                    $this->getColor($hash),
                    $this->getX(),
                    $this->getY(),
                    $this->getRingRadius($i),
                    $this->getRingAngle($i, $hash),
                    $this->getRingWidth(),
                    $this->getRingRotation($i, $hash)
                )
            );
        }

        return (string) $svg;
    }

    /**
     * Returns the background for the image.
     *
     * @return SvgNode
     */
    private function getBackground()
    {
        return (new Circle($this->getX(), $this->getY(), $this->getRadius()))
            ->setFillColor($this->getBackgroundColor())
            ->setStrokeWidth(0);
    }

    /**
     * Returns the center dot for the image.
     *
     * @param Color $color Color for the dot.
     *
     * @return SvgNode
     */
    private function getCenter(Color $color)
    {
        return (new Circle($this->getX(), $this->getY(), $this->getCenterRadius()))
            ->setFillColor($color)
            ->setStrokeWidth(0);
    }

    /**
     * Returns an arc drawn according to the passed specification.
     *
     * @param float $radius Radius of the arc.
     * @param float $angle Angle of the arc.
     * @param float $start Starting angle for the arc.
     *
     * @return SvgNode
     */
    private function getArc(Color $color, $x, $y, $radius, $angle, $width, $start = 0)
    {
        return (new Path(
                $x + $radius * cos(deg2rad($start)),
                $y + $radius * sin(deg2rad($start))
            ))
            ->setFillColor($color)
            ->setStrokeColor($color)
            ->setStrokeWidth(1)
            ->arcTo(
                $x + $radius * cos(deg2rad($start + $angle)),
                $y + $radius * sin(deg2rad($start + $angle)),
                $radius,
                $radius,
                0,
                $angle > 180,
                1
            )
            ->lineTo(
                $x + ($radius + $width) * cos(deg2rad($start + $angle)),
                $y + ($radius + $width) * sin(deg2rad($start + $angle))
            )
            ->arcTo(
                $x + ($radius + $width) * cos(deg2rad($start)),
                $y + ($radius + $width) * sin(deg2rad($start)),
                $radius + $width,
                $radius + $width,
                0,
                $angle > 180,
                0
            )
            ->lineTo(
                $x + $radius * cos(deg2rad($start)),
                $y + $radius * sin(deg2rad($start))
            );
    }

    /**
     * @return float
     */
    private function getRadius()
    {
        return $this->sideLength / 2;
    }

    /**
     * @return float
     */
    private function getX()
    {
        return $this->getRadius();
    }

    /**
     * @return float
     */
    private function getY()
    {
        return $this->getRadius();
    }

    /**
     * @return float
     */
    private function getMultiplier()
    {
        return $this->sideLength / 1000;
    }

    /**
     * @return float
     */
    private function getCenterRadius()
    {
        return 125 * $this->getMultiplier();
    }

    /**
     * Returns the inner radius for a ring
     *
     * @param integer $ring Ring number.
     *
     * @return float
     */
    private function getRingRadius($ring)
    {
        return $ring * 120 * $this->getMultiplier();
    }

    /**
     * Returns the angle in degrees for the given ring, based on the hash.
     *
     * @param integer $ring
     * @param string $hash
     *
     * @return integer
     */
    private function getRingAngle($ring, $hash)
    {
        return 10 * pow(2, 3 - $ring) * array_reduce(
            str_split($hash, pow(2, 3 - $ring)),
            function ($total, $substr) {
                return $total + (hexdec($substr) % 2);
            },
            0
        );
    }

    /**
     * Returns the ring width.
     *
     * @return float
     */
    private function getRingWidth()
    {
        return 125 * $this->getMultiplier();
    }

    /**
     * @param integer $ring
     * @param string $hash
     *
     * @return integer
     */
    private function getRingRotation($ring, $hash)
    {
        return 36 * array_reduce(
            str_split(substr($hash, 0, 30), 3),
            function ($total, $substr) use ($ring) {
                return $total + (hexdec($substr[$ring - 1]) % 2);
            },
            0
        );
    }

    /**
     * Determines whether the center dot should be displayed.
     *
     * @param string $hash
     *
     * @return boolean
     */
    public function showCenter($hash)
    {
        return hexdec(substr($hash, 24, 8)) % 2 === 0;
    }
}
