<?php

declare(strict_types=1);

namespace PictShare\Controllers;

use PictShare\Classes\Exceptions\MethodNotAllowedException;

class BackendController extends AbstractController implements ControllerInterface
{
    /**
     * @TODO Use Response objects.
     * @TODO Decide request method automatically.
     * @TODO Output buffering. Headers?
     */
    final public function get()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (UPLOAD_CODE !== false && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            echo(json_encode(['status' => 'ERR','reason' => 'Wrong upload code provided']));

            return;
        }

        if ($_REQUEST['getimage']) {
            echo json_encode($this->uploadImageFromURL($_REQUEST['getimage']));
        } elseif ($_FILES['postimage']) {
            echo json_encode($this->processSingleUpload('postimage'));
        } elseif ($_REQUEST['base64']) {
            echo json_encode($this->uploadImageFromBase64($_REQUEST['base64']));
        } elseif ($_REQUEST['geturlinfo']) {
            echo json_encode($this->getURLInfo($_REQUEST['geturlinfo']));
        } elseif ($_REQUEST['a'] === 'oembed') {
            echo json_encode($this->oembed($_REQUEST['url'], $_REQUEST['t']));
        }

        echo json_encode(['status' => 'ERR','reason' => 'NO_VALID_COMMAND']);
    }

    /**
     * @throws MethodNotAllowedException
     */
    final public function post()
    {
        throw new MethodNotAllowedException('Method not allowed:' . __FUNCTION__);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    private function processSingleUpload(string $name): array
    {
        if (UPLOAD_CODE && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            exit(json_encode(['status' => 'ERR','reason' => $this->translate(21)]));
        }

        if ($_FILES[$name]['error'] == UPLOAD_ERR_OK) {
            $type = $this->getTypeOfFile($_FILES[$name]['tmp_name']);
            $type = $this->model->isTypeAllowed($type);
            if (!$type) {
                exit(json_encode(['status' => 'ERR','reason' => 'Unsupported type']));
            }

            $data = $this->uploadImageFromURL($_FILES[$name]['tmp_name']);
            if ($data['status'] === 'OK') {
                $o = [
                    'status' => 'OK',
                    'type' => $type,
                    'hash' => $data['hash'],
                    'url' => DOMAINPATH . '/' . $data['hash'],
                    'domain' => DOMAINPATH,
                ];

                if ($data['deletecode']) {
                    $o['deletecode'] = $data['deletecode'];
                }

                return $o;
            }
        }

        return [
            'status' => 'ERR',
            'reason' => 'Unknown',
        ];
    }

    /**
     * @param string $data
     *
     * @return array
     */
    private function uploadImageFromBase64(string $data): array
    {
        $type = $this->base64ToType($data);

        if (!$type) {
            return [
                'status' => 'ERR',
                'reason' => 'wrong filetype',
                'type'   => $type,
            ];
        }

        $hash = $this->getNewHash($type);
        $file = BASE_DIR . 'tmp/' . $hash;

        $this->base64ToImage($data, $file, $type);

        return $this->uploadImageFromURL($file);
    }

    /**
     * @param string $url
     * @param string $type
     *
     * @return array
     */
    private function oembed($url, $type): array
    {
        $data = $this->getURLInfo($url);
        $rawurl = $url . '/raw';
        switch ($type) {
            case 'json':
                header('Content-Type: application/json');
                return [
                    'version' => '1.0',
                    'type' => 'video',
                    'thumbnail_url' => $url . '/preview',
                    'thumbnail_width' => $data['width'],
                    'thumbnail_height' => $data['height'],
                    'width' => $data['width'],
                    'height' => $data['height'],
                    'title' => 'PictShare',
                    'provider_name' => 'PictShare',
                    'provider_url' => DOMAINPATH,
                    'html' => '<video id="video" poster="' . $url . '/preview' . '" preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline>
							       <source src="' . $rawurl . '" type="video/mp4">
            				   </video>'
                ];
                break;

            case 'xml':
                break;
        }

        return [];
    }

    /**
     * @param string $base64_string
     *
     * @return bool|string
     */
    private function base64ToType(string $base64_string)
    {
        $data = explode(',', $base64_string);
        $data = $data[1];

        $data = str_replace(' ', '+', $data);
        $data = base64_decode($data);

        $info = getimagesizefromstring($data);

        trigger_error('########## FILETYPE: ' . $info['mime']);

        $f = finfo_open();

        return $this->model->isTypeAllowed(finfo_buffer($f, $data, FILEINFO_MIME_TYPE));
    }

    /**
     * @param string $base64_string
     * @param string $output_file
     * @param string $type
     *
     * @return string
     */
    private function base64ToImage(string $base64_string, string $output_file, string $type): string
    {
        $data = explode(',', $base64_string);
        $data = $data[1];

        $data = str_replace(' ', '+', $data);

        $data = base64_decode($data);

        $source = imagecreatefromstring($data);
        switch ($type) {
            case 'jpg':
                imagejpeg($source, $output_file, (\defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
                trigger_error('========= SAVING AS ' . $type . ' TO ' . $output_file);
                break;

            case 'png':
                imagefill($source, 0, 0, IMG_COLOR_TRANSPARENT);
                imagesavealpha($source, true);
                imagepng($source, $output_file, (\defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                trigger_error('========= SAVING AS ' . $type . ' TO ' . $output_file);
                break;

            case 'gif':
                imagegif($source, $output_file);
                trigger_error('========= SAVING AS ' . $type . ' TO ' . $output_file);
                break;

            default:
                imagefill($source, 0, 0, IMG_COLOR_TRANSPARENT);
                imagesavealpha($source, true);
                imagepng($source, $output_file, (\defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                break;
        }

        imagedestroy($source);

        return $type;
    }
}
