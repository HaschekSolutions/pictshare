<?php

//spl_autoload_register('autoload');
//
//function autoload($className)
//{
//    if (file_exists(ROOT . DS . 'models' . DS . strtolower($className) . '.php')) {
//        require_once(ROOT . DS . 'models' . DS . strtolower($className) . '.php');
//    }
//    if (file_exists(ROOT . DS . 'classes' . DS . strtolower($className) . '.php')) {
//        require_once(ROOT . DS . 'classes' . DS . strtolower($className) . '.php');
//    }
//}

/**
 * @param string $url
 *
 * @return void
 */
function callHook($url)
{
    route($url);
}

/**
 * @param string $url
 *
 * @return void
 */
function route($url)
{
    $pm   = new \App\Models\PictshareModel();
    $view = new \App\Support\View();

    $data = $pm->urlToData($url);

    if (!is_array($data) || !$data['hash']) {
        if ((UPLOAD_FORM_LOCATION && $url == UPLOAD_FORM_LOCATION) || (!UPLOAD_FORM_LOCATION)) {
            $upload_answer = $pm->processUploads();
            if ($upload_answer) {
                $o = $upload_answer;
            } else {
                $o = $pm->renderUploadForm();
            }

            $vars['content'] = $o;
            $vars['slogan']  = $pm->translate(2);
        }

        if (!isset($vars) && LOW_PROFILE) {
            header('HTTP/1.0 404 Not Found');
            exit();
        } else {
            if (!isset($vars)) {
                $vars['content'] = $pm->translate(12);
                $vars['slogan']  = $pm->translate(2);
            }
        }

        $view->render($vars);
    } else {
        if (isset($data['album']) && $data['album']) {
            $view->renderAlbum($data);
        } else {
            $view->renderImage($data);
        }
    }
}
