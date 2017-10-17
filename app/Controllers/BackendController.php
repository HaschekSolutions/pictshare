<?php

namespace App\Controllers;

use App\Models\PictshareModel;

/**
 * Class BackendController
 * @package App\Controllers
 */
class BackendController
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
     * @param array $reqParams
     *
     * @return void
     */
    public function processRequest($reqParams)
    {
        if (UPLOAD_CODE != false && !$this->pictshareModel->uploadCodeExists($reqParams['upload_code'])) {
            exit(json_encode(['status' => 'ERR', 'reason' => 'Wrong upload code provided']));
        }

        if ($reqParams['getimage']) {
            $url = $reqParams['getimage'];

            echo json_encode($this->pictshareModel->uploadImageFromURL($url));
        } else {
            if ($_FILES['postimage']) {
                $image = $_FILES['postimage'];
                echo json_encode($this->pictshareModel->processSingleUpload($image, 'postimage'));
            } else {
                if ($reqParams['base64']) {
                    $data   = $reqParams['base64'];
                    $format = $reqParams['format'];
                    echo json_encode($this->pictshareModel->uploadImageFromBase64($data, $format));
                } else {
                    if ($reqParams['geturlinfo']) {
                        echo json_encode($this->pictshareModel->getURLInfo($reqParams['geturlinfo']));
                    } else {
                        if ($reqParams['a'] == 'oembed') {
                            echo json_encode($this->pictshareModel->oembed($reqParams['url'], $reqParams['t']));
                        } else {
                            echo json_encode(['status' => 'ERR', 'reason' => 'NO_VALID_COMMAND']);
                        }
                    }
                }
            }
        }
    }
}
