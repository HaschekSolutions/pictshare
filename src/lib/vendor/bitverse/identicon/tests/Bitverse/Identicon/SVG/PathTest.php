<?php

namespace Bitverse\Identicon\SVG;

class PathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider lineProvider
     */
    public function testLineTo($x, $y, $coords, $result)
    {
        $path = new Path($x, $y);

        foreach ($coords as $coord) {
            $path->lineTo($coord['x'], $coord['y'], $coord['relative']);
        }

        $this->assertEquals($result, (string) $path);
    }

    public function lineProvider()
    {
        return [
            'straight line' => [
                0,
                10,
                [
                    ['x' => 5, 'y' => -1, 'relative' => false]
                ],
                '<path fill="" stroke="" stroke-width="" d="M 0,10 L 5,-1" />'
            ],
            'straight relative line' => [
                0,
                10,
                [
                    ['x' => -5, 'y' => -11, 'relative' => true]
                ],
                '<path fill="" stroke="" stroke-width="" d="M 0,10 l -5,-11" />'
            ],
            'two lines' => [
                0,
                10,
                [
                    ['x' => 5, 'y' => -1, 'relative' => false],
                    ['x' => 1, 'y' => 1, 'relative' => true]
                ],
                '<path fill="" stroke="" stroke-width="" d="M 0,10 L 5,-1 l 1,1" />'
            ]
        ];
    }
}
