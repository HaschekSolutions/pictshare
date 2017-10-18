<?php

namespace App\Controllers;

use App\Models\PictshareModel;
use App\Support\ConfigInterface;
use App\Support\Utils;
use App\Views\View;

/**
 * Class IndexController
 * @package App\Controllers
 */
class IndexController
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
     * @var View
     */
    protected $view;

    /**
     * CliController constructor.
     *
     * @param ConfigInterface $config
     * @param PictshareModel  $pictshareModel
     * @param View            $view
     */
    public function __construct(ConfigInterface $config, PictshareModel $pictshareModel, View $view)
    {
        $this->config         = $config;
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
            $uploadFormLocation = $this->config->get('app.upload_form_location');

            if (($uploadFormLocation && $url == $uploadFormLocation) || (!$uploadFormLocation)) {
                $upload_answer = $this->pictshareModel->processUploads();
                if ($upload_answer) {
                    $o = $upload_answer;
                } else {
                    $o = $this->pictshareModel->renderUploadForm();
                }

                $vars['content'] = $o;
                $vars['slogan']  = $this->pictshareModel->translate(2);
            }

            if (!isset($vars) && $this->config->get('app.low_profile', false)) {
                header('HTTP/1.0 404 Not Found');
                exit();
            } elseif (!isset($vars)) {
                $vars['content'] = $this->pictshareModel->translate(12);
                $vars['slogan']  = $this->pictshareModel->translate(2);
            }

            $this->view->render($vars);
        } elseif (isset($data['album']) && $data['album']) {
            $this->view->renderAlbum($data);
        } else {
            $this->view->renderImage($data);
        }
    }
}
