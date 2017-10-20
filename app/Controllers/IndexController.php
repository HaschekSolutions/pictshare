<?php

namespace App\Controllers;

use App\Models\PictshareModel;
use App\Support\Translator;
use App\Support\Utils;
use App\Views\View;

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
            $uploadFormLocation = config('app.upload_form_location');

            if (($uploadFormLocation && $url == $uploadFormLocation) || (!$uploadFormLocation)) {
                $upload_answer = $this->pictshareModel->processUploads();
                if ($upload_answer) {
                    $o = $upload_answer;
                } else {
                    $o = $this->view->renderUploadForm();
                }

                $vars['content'] = $o;
                $vars['slogan']  = Translator::translate(2);
            }

            if (!isset($vars) && config('app.low_profile', false)) {
                header('HTTP/1.0 404 Not Found');
                exit();
            } elseif (!isset($vars)) {
                $vars['content'] = Translator::translate(12);
                $vars['slogan']  = Translator::translate(2);
            }

            $this->view->render($vars);
        } elseif (isset($data['album']) && $data['album']) {
            $this->view->renderAlbum($data);
        } else {
            $this->view->renderFile($data);
        }
    }
}
