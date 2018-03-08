<?php

namespace App\Providers;

use App\Config\ConfigInterface;
use App\Controllers\BackendController;
use App\Controllers\CliController;
use App\Controllers\IndexController;
use App\Models\PictshareModel;
use App\Support\Database;
use App\Transformers\Image;
use App\Views\View;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Mustache_Engine;

/**
 * Class ServiceProvider
 * @package App\Providers
 */
class ServiceProvider extends AbstractServiceProvider
{
    /**
     * The provides array is a way to let the container
     * know that a service is provided by this service
     * provider. Every service that is registered via
     * this service provider must have an alias added
     * to this array or it will be ignored.
     *
     * @var array
     */
    protected $provides = [
        ConfigInterface::class,
        IndexController::class,
        BackendController::class,
        CliController::class,
        PictshareModel::class,
        View::class,
        Image::class,
        Mustache_Engine::class,
    ];

    /**
     * This is where the magic happens, within the method you can
     * access the container and register or retrieve anything
     * that you need to, but remember, every alias registered
     * within this method must be declared in the `$provides` array.
     */
    public function register()
    {
        $this->getContainer()->share(ConfigInterface::class, function () {
            /*
             * Load the configuration
             * ----------------------
             *
             */
            $config = new \App\Config\Config(require_once __DIR__ . '/../../config/config.php');

            // this is support for "old" configuration through 'config.inc.php' file
            if (file_exists(__DIR__.'/../../inc/config.inc.php')) {
                include_once __DIR__.'/../../inc/config.inc.php';
                $config->setFromConstants();
            }

            return $config;
        });

        $this->registerVendor();
        $this->registerDatabase();
        $this->registerControllers();
        $this->registerModels();
        $this->registerViews();
        $this->registerTransformers();
        $this->registerHelpers();
    }

    /**
     * Register vendor classes.
     */
    protected function registerVendor()
    {
        $this->getContainer()->add(Mustache_Engine::class);
    }

    /**
     * Register database manager.
     */
    protected function registerDatabase()
    {
        $container = $this->getContainer();
        $container->share(Database::class, function () use ($container) {
            $config   = $container->get(ConfigInterface::class);
            $dbConfig = $config->get('database', []);

            return new Database($dbConfig);
        });
    }

    /**
     * Register controllers.
     */
    protected function registerControllers()
    {
        $this->getContainer()
            ->add(IndexController::class)
            ->withArguments([PictshareModel::class, View::class]);

        $this->getContainer()
            ->add(BackendController::class)
            ->withArgument(PictshareModel::class);

        $this->getContainer()
            ->add(CliController::class)
            ->withArgument(PictshareModel::class);
    }

    /**
     * Register models.
     */
    protected function registerModels()
    {
        $this->getContainer()
            ->share(PictshareModel::class)
            ->withArguments([ConfigInterface::class, Image::class, Database::class]);
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $this->getContainer()
            ->share(View::class)
            ->withArguments([ConfigInterface::class, PictshareModel::class, Image::class, Mustache_Engine::class]);
    }

    /**
     * Register transformers.
     */
    protected function registerTransformers()
    {
        $this->getContainer()->share(Image::class);
    }

    /**
     * Register helpers.
     */
    protected function registerHelpers()
    {
        ;
    }
}
