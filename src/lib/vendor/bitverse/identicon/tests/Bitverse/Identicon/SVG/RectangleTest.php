<?php

namespace Bitverse\Identicon\SVG;

class RectangleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider rectangleProvider
     */
    public function testRectangle($x, $y, $width, $height, $result)
    {
        $rect = new Rectangle($x, $y, $width, $height);

        $this->assertEquals($result, (string) $rect);
    }

    public function rectangleProvider()
    {
        return [
            'no offset' => [
                0,
                0,
                50,
                50,
                '<rect x="0" y="0" width="50" height="50" ' .
                    'fill="" stroke="" stroke-width="0" />'
            ],
            'with offset' => [
                20,
                15,
                200,
                100,
                '<rect x="20" y="15" width="200" height="100" ' .
                    'fill="" stroke="" stroke-width="0" />'
            ]
        ];
    }
}
