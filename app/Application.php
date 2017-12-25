<?php

namespace App;

use App\Config\ConfigInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;

/**
 * Class Application
 * @package App
 */
class Application implements ContainerAwareInterface
{
    /**
     * The globally available Application instance.
     *
     * @var static
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var string
     */
    protected $relativePath;

    /**
     * @var string
     */
    protected $domainPath;

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

        $this->configureOtherPaths();
        $this->configureErrorReporting();

        static::setInstance($this);
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
     * @return string
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * @return string
     */
    public function getDomainPath()
    {
        return $this->domainPath;
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
     * Return configuration instance.
     *
     * @return ConfigInterface|mixed
     */
    public function getConfig()
    {
        return $this->config;
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
     * Configure relative path and domain.
     *
     * @return void
     */
    protected function configureOtherPaths()
    {
        $this->relativePath = ((dirname($_SERVER['PHP_SELF']) == '/' ||
                                dirname($_SERVER['PHP_SELF']) == '\\' ||
                                dirname($_SERVER['PHP_SELF']) == '/index.php' ||
                                dirname($_SERVER['PHP_SELF']) == '/backend.php')
                              ? '/' : dirname($_SERVER['PHP_SELF']) . '/');

        if ($forceDomain = $this->config->get('app.force_domain')) {
            $this->domainPath = $forceDomain;
        } else {
            $this->domainPath = (( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') .
                                '://' . $_SERVER['HTTP_HOST'];
        }
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

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Set the shared instance of the Application.
     *
     * @param Application $app
     *
     * @return void
     */
    protected static function setInstance(Application $app)
    {
        static::$instance = $app;
    }
}
