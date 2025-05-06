<?php

namespace Bitverse\Identicon\Preprocessor;

class MD5Preprocessor implements PreprocessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process($string)
    {
        return md5($string);
    }
}
