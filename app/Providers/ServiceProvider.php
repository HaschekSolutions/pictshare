<?php

namespace App\Providers;

use App\Controllers\BackendController;
use App\Controllers\CliController;
use App\Controllers\IndexController;
use App\Factories\ImageFactory;
use App\Models\PictshareModel;
use App\Support\ConfigInterface;
use App\Support\HTML;
use App\Transformers\Image;
use App\Views\View;
use League\Container\ServiceProvider\AbstractServiceProvider;

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
        //ImageFactory::class
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
            $config = new \App\Support\Config(require_once __DIR__ . '/../../config/config.php');

            // this is support for "old" configuration through 'config.inc.php' file
            if (file_exists(__DIR__.'/../../inc/config.inc.php')) {
                include_once __DIR__.'/../../inc/config.inc.php';
                $config->setFromConstants();
            }

            return $config;
        });

        $this->registerControllers();
        $this->registerModels();
        $this->registerViews();
        $this->registerTransformers();
        //$this->registerFactories();
    }

    /**
     * Register controllers.
     */
    protected function registerControllers()
    {
        $this->getContainer()
            ->add(IndexController::class)
            ->withArguments([ConfigInterface::class, PictshareModel::class, View::class]);

        $this->getContainer()
            ->add(BackendController::class)
            ->withArguments([ConfigInterface::class, PictshareModel::class]);

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
            ->add(PictshareModel::class)
            ->withArguments([ConfigInterface::class, HTML::class]);
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $this->getContainer()
            ->add(View::class)
            ->withArguments([ConfigInterface::class, Image::class, PictshareModel::class, \Mustache_Engine::class]);
    }

    /**
     * Register transformers.
     */
    protected function registerTransformers()
    {
        $this->getContainer()
            ->add(Image::class)
            ->withArguments([ConfigInterface::class, PictshareModel::class]);
    }

    /**
     * Register factories.
     */
    protected function registerFactories()
    {
        $this->getContainer()
            ->add(ImageFactory::class)
            ->withArguments([ConfigInterface::class, PictshareModel::class]);
    }
}
