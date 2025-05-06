<?php

namespace Bitverse\Identicon;

use Bitverse\Identicon\Color\Color;
use Bitverse\Identicon\Generator\GeneratorInterface;
use Bitverse\Identicon\Preprocessor\PreprocessorInterface;

class Identicon
{
    /**
     * @var PreprocessorInterface
     */
    private $preprocessor;

    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * @param GeneratorInterface $generator
     */
    public function __construct(
        PreprocessorInterface $preprocessor,
        GeneratorInterface $generator
    ) {
        $this->preprocessor = $preprocessor;
        $this->generator = $generator;
    }

    /**
     * Returns the generated svg mockup for the icon.
     *
     * @param string $string String to generate the icon from.
     * @return string
     */
    public function getIcon($string)
    {
        return $this->generator->generate(
            $this->preprocessor->process($string)
        );
    }
}
