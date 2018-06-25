<?php

declare(strict_types=1);

namespace PictShare\Controllers;

use PictShare\Classes\Exceptions\MethodNotAllowedException;

class CliController extends AbstractController implements ControllerInterface
{
    /**
     * @var array
     */
    private $args;

    /**
     * @param array $args
     *
     * @return self
     *
     * @TODO Get rid of this.
     */
    final public function setArgs(array $args): self
    {
        // Lose first param (self name).
        array_shift($args);

        $this->args = $args;

        return $this;
    }

    /**
     * @TODO Use Response objects.
     * @TODO Decide request method automatically.
     * @TODO Output buffering. Headers?
     */
    final public function get()
    {
        switch ($this->args[0]) {
            case 'mp4convert':
                list($hash, $path) = $this->args;

                $source = $path . $hash;

                if (!$this->model->isImage($hash)) {
                    exit('[x] Hash not found' . "\n");
                }

                echo "[i] Converting $hash to mp4\n";

                $this->saveAsMP4($source, $path . 'mp4_1.' . $hash);
                $this->saveAsMP4($source, $path . 'ogg_1.' . $hash);

                break;
        }

        return ['status' => 'ok'];
    }

    /**
     * @throws MethodNotAllowedException
     */
    final public function post()
    {
        throw new MethodNotAllowedException('Method not allowed:' . __FUNCTION__);
    }

    /**
     * @param string $source
     * @param string $target
     */
    private function saveAsMP4(string $source, string $target)
    {
        $bin = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $h265 = "$bin -y -i $source -an -c:v libx264 -qp 0 -f mp4 $target";
        system($h265);
    }
}
