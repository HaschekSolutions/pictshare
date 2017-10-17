<?php

namespace App;

use App\Support\Config;

/**
 * Class Application
 * @package App
 */
class Application
{
    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var Config
     */
    protected $config;

    /**
     * App constructor.
     *
     * @param string $rootPath
     * @param Config $config
     */
    public function __construct($rootPath, Config $config)
    {
        $this->rootPath = $rootPath;
        $this->config   = $config;

        $this->configureErrorReporting();
    }

    /**
     * Return root path of the application.
     *
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * Configure and start the session.
     *
     * @return void
     */
    public function sessionStart()
    {
        session_cache_limiter($this->config->get('session.cache_limiter'));
        session_cache_expire($this->config->get('session.cache_expire') * 24 * 60);
        session_start();
    }

    /**
     * Configure error reporting.
     *
     * @return void
     */
    protected function configureErrorReporting()
    {
        error_reporting(E_ALL & ~E_NOTICE);
        if ($this->config->get('app.show_errors')) {
            ini_set('display_errors', 'On');
        } else {
            ini_set('display_errors', 'Off');
        }
    }
}
