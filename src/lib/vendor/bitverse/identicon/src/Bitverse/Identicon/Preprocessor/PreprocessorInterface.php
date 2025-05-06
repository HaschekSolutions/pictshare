<?php

namespace Bitverse\Identicon\Preprocessor;

interface PreprocessorInterface
{
    /**
     * Prepares the string to a format that will be parsed
     * by the actual generator.
     *
     * @param string $string
     * @return string
     */
    public function process($string);
}
