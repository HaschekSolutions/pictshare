<?php

namespace App\Controllers;

use App\Models\PictshareModel;
use App\Support\Utils;
use App\Support\View;

/**
 * Class IndexController
 * @package App\Controllers
 */
class IndexController
{
    /**
     * @var PictshareModel
     */
    protected $pictshareModel;

    /**
     * @var View
     */
    protected $view;

    /**
     * CliController constructor.
     *
     * @param PictshareModel $pictshareModel
     * @param View           $view
     */
    public function __construct(PictshareModel $pictshareModel, View $view)
    {
        $this->pictshareModel = $pictshareModel;
        $this->view           = $view;
    }

    /**
     * @param string $url
     *
     * @return void
     */
    public function processUrl($url)
    {
        Utils::removeMagicQuotes();

        $data = $this->pictshareModel->urlToData($url);

        if (!is_array($data) || !$data['hash']) {
            if ((UPLOAD_FORM_LOCATION && $url == UPLOAD_FORM_LOCATION) || (!UPLOAD_FORM_LOCATION)) {
                $upload_answer = $this->pictshareModel->processUploads();
                if ($upload_answer) {
                    $o = $upload_answer;
                } else {
                    $o = $this->pictshareModel->renderUploadForm();
                }

                $vars['content'] = $o;
                $vars['slogan']  = $this->pictshareModel->translate(2);
            }

            if (!isset($vars) && LOW_PROFILE) {
                header('HTTP/1.0 404 Not Found');
                exit();
            } else {
                if (!isset($vars)) {
                    $vars['content'] = $this->pictshareModel->translate(12);
                    $vars['slogan']  = $this->pictshareModel->translate(2);
                }
            }

            $this->view->render($vars);
        } else {
            if (isset($data['album']) && $data['album']) {
                $this->view->renderAlbum($data);
            } else {
                $this->view->renderImage($data);
            }
        }
    }
}
