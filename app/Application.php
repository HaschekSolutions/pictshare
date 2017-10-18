<?php

namespace App;

use App\Support\ConfigInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;

/**
 * Class Application
 * @package App
 */
class Application implements ContainerAwareInterface
{
    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * App constructor.
     *
     * @param string             $rootPath
     * @param ContainerInterface $container
     */
    public function __construct($rootPath, ContainerInterface $container)
    {
        $this->rootPath  = $rootPath;
        $this->container = $container;
        $this->config    = $container->get(ConfigInterface::class);

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
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getContainer()
    {
        return $this->container;
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
