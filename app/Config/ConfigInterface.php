<?php

namespace App\Config;

/**
 * Interface ConfigInterface
 * @package App\Support
 */
interface ConfigInterface
{
    /**
     * Set a configuration item.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return array
     */
    public function set($key, $value);

    /**
     * Check if an item exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Get a configuration item.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed config value or default when not found
     */
    public function get($key, $default = null);
}
