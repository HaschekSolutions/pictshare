<?php

declare(strict_types=1);

namespace PictShare\Classes;

use PictShare\Classes\Filters\FilterInterface;

class FilterFactory
{
    /**
     * Maps filter names from the URL, to class names.
     *
     * @TODO Get rid of this. We can get the classes to define their own URL name.
     */
    const FILTER_MAP = [
        'aqua' => 'Aqua',
        'blur' => 'Blur',
        'boost' => 'Boost',
        'brightness' => 'Brightness',
        'bubbles' => 'Bubbles',
        'colorize' => 'Colorize',
        'contrast' => 'Contrast',
        'cool' => 'Cool',
        'edgedetect' => 'EdgeDetect',
        'emboss' => 'Emboss',
        'fuzzy' => 'Fuzzy',
        'gray' => 'Gray,',
        'grayscale' => 'Grayscale',
        'light' => 'Light',
        'negative' => 'Negative',
        'old' => 'OldOne',
        'old2' => 'OldTwo',
        'old3' => 'OldThree',
        'pixelate' => 'Pixelate',
        'rotate' => 'Rotate',
        'sepia' => 'Sepia',
        'sharpen' => 'Sharpen',
        'smooth' => 'Smooth',
    ];

    /**
     * @param string $filterUrlName
     *
     * @return FilterInterface
     *
     * @throws \DomainException
     */
    public static function getFilter(string $filterUrlName): FilterInterface
    {
        if (!\array_key_exists($filterUrlName, static::FILTER_MAP)) {
            throw new \DomainException('Filter for URL name ' . $filterUrlName . ' not found.');
        }

        $className = __NAMESPACE__ . '\\Filters\\' . static::FILTER_MAP[$filterUrlName] . 'Filter';

        return new $className();
    }

    /**
     * @param string $filterUrlName
     *
     * @return bool
     */
    public static function isValidFilter(string $filterUrlName): bool
    {
        return \array_key_exists($filterUrlName, self::FILTER_MAP);
    }
}
