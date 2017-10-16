<?php

namespace App\Support;

/**
 * Class Config
 * @package App\Support
 */
class Config
{
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * Set a setting.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return array
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $tmp  = &$this->configuration;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($tmp[$key]) || ! is_array($tmp[$key])) {
                $tmp[$key] = [];
            }

            $tmp = &$tmp[$key];
        }

        $tmp[array_shift($keys)] = $value;

        return $tmp;
    }

    /**
     * Check if an item exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        if (empty($this->configuration) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $this->configuration)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($this->configuration) || ! array_key_exists($segment, $this->configuration)) {
                return false;
            }

            $this->configuration = $this->configuration[$segment];
        }

        return true;
    }

    /**
     * Get a configuration value.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed config value or default when not found
     */
    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return $this->configuration;
        }

        if (isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($this->configuration) || ! array_key_exists($segment, $this->configuration)) {
                return value($default);
            }

            $this->configuration = $this->configuration[$segment];
        }

        return $this->configuration;
    }

    /**
     * @return void
     */
    public function setFromConstants()
    {
        foreach ($this->get('app') as $key => &$val) {
            $upKey = strtoupper($key);
            if (defined($upKey)) {
                $val = constant($upKey);
            }
        }
    }
}
