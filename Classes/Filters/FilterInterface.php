<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

interface FilterInterface
{
    /**
     * Applies the filter to an image.
     *
     * @return FilterInterface
     */
    public function apply(): FilterInterface;

    /**
     * Get the default filter options.
     *
     * @return array
     */
    public function getDefaults(): array;

    /**
     * Get the custom filter settings.
     *
     * @return array
     */
    public function getSettings(): array;

    /**
     * Set settings for the filter.
     *
     * @param array $settings
     *
     * @return FilterInterface
     */
    public function setSettings(array $settings): FilterInterface;

    /**
     * Return the GD image resource from the filter.
     *
     * @return resource
     */
    public function getImage();

    /**
     * Set the starting image.
     *
     * @param resource $image
     *
     * @return self
     */
    public function setImage($image): FilterInterface;
}
