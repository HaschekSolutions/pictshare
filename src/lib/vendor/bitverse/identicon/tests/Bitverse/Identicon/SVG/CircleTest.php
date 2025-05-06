<?php

namespace Bitverse\Identicon\SVG;

class CircleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider circleProvider
     */
    public function testCircle($x, $y, $radius, $result)
    {
        $circle = new Circle($x, $y, $radius);

        $this->assertEquals($result, (string) $circle);
    }

    public function circleProvider()
    {
        return [
            'offset circle' => [
                3,
                8,
                15,
                '<circle cx="3" cy="8" r="15" fill="" stroke="" stroke-width="0" />'
            ]
        ];
    }
}
