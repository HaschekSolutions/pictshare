<?php

namespace App\Controllers;

use App\Models\PictshareModel;

/**
 * Class CliController
 * @package App\Controllers
 */
class CliController
{
    /**
     * @var PictshareModel
     */
    protected $pictshareModel;

    /**
     * CliController constructor.
     *
     * @param PictshareModel $pictshareModel
     */
    public function __construct(PictshareModel $pictshareModel)
    {
        $this->pictshareModel = $pictshareModel;
    }

    /**
     * @param array $argv
     *
     * @return void
     */
    public function processCommand($argv)
    {
        //$action = $argv[2];
        $params = $argv;

        // lose first param (self name)
        array_shift($params);

        $this->pictshareModel->backend($params);
    }
}
