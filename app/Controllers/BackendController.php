<?php

namespace App\Controllers;

use App\Models\PictshareModel;
use App\Support\ConfigInterface;

/**
 * Class BackendController
 * @package App\Controllers
 */
class BackendController
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
     * CliController constructor.
     *
     * @param ConfigInterface $config
     * @param PictshareModel  $pictshareModel
     */
    public function __construct(ConfigInterface $config, PictshareModel $pictshareModel)
    {
        $this->pictshareModel = $pictshareModel;
        $this->config         = $config;
    }

    /**
     * @param array $reqParams
     *
     * @return void
     */
    public function processRequest($reqParams)
    {
        if ($this->config->get('app.upload_code', false) != false &&
            !$this->pictshareModel->uploadCodeExists($reqParams['upload_code'])
        ) {
            exit(json_encode(['status' => 'ERR', 'reason' => 'Wrong upload code provided']));
        }

        if ($reqParams['getimage']) {
            $url = $reqParams['getimage'];
            echo json_encode($this->pictshareModel->uploadImageFromURL($url));

        } elseif ($_FILES['postimage']) {
            $image = $_FILES['postimage'];
            echo json_encode($this->pictshareModel->processSingleUpload($image, 'postimage'));

        } elseif ($reqParams['base64']) {
            $data   = $reqParams['base64'];
            $format = $reqParams['format'];
            echo json_encode($this->pictshareModel->uploadImageFromBase64($data, $format));

        } elseif ($reqParams['geturlinfo']) {
            echo json_encode($this->pictshareModel->getURLInfo($reqParams['geturlinfo']));

        } elseif ($reqParams['a'] == 'oembed') {
            echo json_encode($this->pictshareModel->oembed($reqParams['url'], $reqParams['t']));

        } else {
            echo json_encode(['status' => 'ERR', 'reason' => 'NO_VALID_COMMAND']);
        }
    }
}
