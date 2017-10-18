<?php

namespace App\Factories;

use App\Models\PictshareModel;
use App\Support\ConfigInterface;
use App\Transformers\Image;

/**
 * Class ImageFactory
 * @package App\Factories
 */
class ImageFactory
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var PictshareModel
     */
    protected $pictshareModel;

    /**
     * ImageFactory constructor.
     *
     * @param ConfigInterface $config
     * @param PictshareModel  $pictshareModel
     */
    public function __construct(ConfigInterface $config, PictshareModel $pictshareModel)
    {
        $this->config         = $config;
        $this->pictshareModel = $pictshareModel;
    }

    /**
     * Creates new Image instance.
     *
     * @return Image
     */
    public function create()
    {
        $image = new Image($this->config, $this->pictshareModel);
        return $image;
    }
}
