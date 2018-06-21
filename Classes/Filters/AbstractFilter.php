<?php

declare(strict_types=1);

namespace PictShare\Classes\Filters;

abstract class AbstractFilter implements FilterInterface
{
    const ASSETS_DIR = '/assets/';

    /**
     * Directory for image assets.
     *
     * @var string
     */
    protected $assetDirectory;

    /**
     * GD image resource.
     *
     * @var resource
     */
    protected $image;

    /**
     * Array of filter settings.
     *
     * @var array
     */
    protected $settings;


    /**
     * @TODO Avoid the dirname call.
     */
    public function __construct()
    {
        $this->settings       = [];
        $this->assetDirectory = \dirname(__FILE__, 4) . self::ASSETS_DIR;
    }

    /**
     * Get the filter image.
     *
     * @return resource
     */
    final public function getImage()
    {
        return $this->image;
    }

    /**
     * @inheritdoc
     *
     * @return self
     */
    final public function setImage($image): FilterInterface
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return array
     */
    final public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @inheritdoc
     */
    final public function setSettings(array $settings): FilterInterface
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @param string $valueHandle
     *
     * @return int
     *
     * @throws \UnexpectedValueException
     */
    final public function getSettingsValue(string $valueHandle): int
    {
        if (array_key_exists($valueHandle, $this->getSettings())) {
            return (int) $this->getSettings()[$valueHandle];
        }

        if (array_key_exists($valueHandle, $this->getDefaults())) {
            return (int) $this->getDefaults()[$valueHandle];
        }

        throw new \UnexpectedValueException(
            'Settings' . $valueHandle . ' not found for filter ' . \get_class($this)
        );
    }

    /**
     * Get default values for the filter.
     *
     * @return array
     */
    abstract public function getDefaults(): array;
}
