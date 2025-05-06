<?php

namespace Bitverse\Identicon\SVG;

class SvgTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider childrenProvider
     */
    public function testSvg($width, $height, $children, $result)
    {
        $svg = new Svg($width, $height);

        foreach ($children as $child) {
            $svg->addChild($child);
        }

        $this->assertEquals($result, (string) $svg);
    }

    public function childrenProvider()
    {
        return [
            'no children' => [
                100,
                100,
                [],
                '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ' .
                    'width="100" height="100"></svg>'
            ],
            'two children' => [
                100,
                100,
                [
                    new TestNode('<child1 />'),
                    new TestNode('<child2 />')
                ],
                '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ' .
                    'width="100" height="100"><child1 /><child2 /></svg>'
            ]
        ];
    }
}

class TestNode extends SvgNode
{
    private $markup;

    public function __construct($markup)
    {
        $this->markup = $markup;
    }

    public function __toString()
    {
        return $this->markup;
    }
}
