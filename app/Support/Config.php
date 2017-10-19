<?php

namespace App\Support;

/**
 * Class Config
 * @package App\Support
 */
class Config implements ConfigInterface
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function has($key)
    {
        if (empty($this->configuration) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $this->configuration)) {
            return true;
        }

        $tmp = &$this->configuration;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($tmp) || ! array_key_exists($segment, $tmp)) {
                return false;
            }

            $tmp = &$tmp[$segment];
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return $this->configuration;
        }

        if (isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }

        $tmp = $this->configuration;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($tmp) || ! array_key_exists($segment, $tmp)) {
                return value($default);
            }

            $tmp = $tmp[$segment];
        }

        return $tmp;
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
