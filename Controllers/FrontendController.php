<?php

declare(strict_types=1);

namespace PictShare\Controllers;

use PictShare\Classes\Exceptions\MethodNotAllowedException;
use PictShare\Classes\FilterFactory;

class FrontendController extends AbstractController implements ControllerInterface
{
    /**
     * @TODO Use Response objects.
     * @TODO Decide request method automatically.
     * @TODO Output buffering. Headers?
     */
    final public function get()
    {
        $this->removeMagicQuotes();

        $url  = $_GET['url'];
        $data = $this->urlToData($url);
        $vars = null;

        if (!\is_array($data) || !$data['hash']) {
            if ((UPLOAD_FORM_LOCATION && $url === UPLOAD_FORM_LOCATION) || (!UPLOAD_FORM_LOCATION)) {
                $upload_answer = $this->processUploads();
                if ($upload_answer) {
                    $o = $upload_answer;
                } else {
                    $o = $this->renderUploadForm();
                }

                $vars['content'] = $o;
                $vars['slogan'] = $this->translate(2);
            }

            if (!$vars && LOW_PROFILE) {
                header('HTTP/1.0 404 Not Found');
                exit();
            }

            if (!$vars) {
                $vars['content'] = $this->translate(12);
                $vars['slogan'] = $this->translate(2);
            }

            $this->render($vars);
        } elseif ($data['album']) {
            $this->renderAlbum($data);
        } else {
            $this->renderImage($data);
        }
    }

    /**
     * @throws MethodNotAllowedException
     */
    final public function post()
    {
        throw new MethodNotAllowedException('Method not allowed:' . __FUNCTION__);
    }

    /**
     * @return null|string
     */
    private function processUploads()
    {
        if ($_POST['submit'] !== $this->translate(3)) {
            return null;
        }

        if (UPLOAD_CODE && !$this->uploadCodeExists($_REQUEST['upload_code'])) {
            return '<span class="error">' . $this->translate(21) . '</span>';
        }

        $i      = 0;
        $o      = '';
        $hashes = [];

        foreach ($_FILES['pic']['error'] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $data = $this->uploadImageFromURL($_FILES['pic']['tmp_name'][$key]);

                if ($data['status'] === 'OK') {
                    if ($data['deletecode']) {
                        $deletecode = '<br/><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] . '/delete_' . $data['deletecode'] . '">Delete image</a>';
                    } else {
                        $deletecode = '';
                    }
                    if ($data['type'] === 'mp4') {
                        $o .= '<div><h2>' . $this->translate(4) . ' ' . ++$i . '</h2><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] . '">' . $data['hash'] . '</a>' . $deletecode . '</div>';
                    } else {
                        $o .= '<div><h2>' . $this->translate(4) . ' ' . ++$i . '</h2><a target="_blank" href="' . DOMAINPATH . PATH . $data['hash'] . '"><img src="' . DOMAINPATH . PATH . '300/' . $data['hash'] . '" /></a>' . $deletecode . '</div>';
                    }

                    $hashes[] = $data['hash'];
                }
            }
        }

        if (count($hashes) > 1) {
            $albumlink = DOMAINPATH . PATH . implode('/', $hashes);
            $o .= '<hr/><h1>Album link</h1><a href="' . $albumlink . '" >' . $albumlink . '</a>';

            $iframe = '<iframe frameborder="0" width="100%" height="500" src="' . $albumlink . '/300x300/forcesize/embed" <p>iframes are not supported by your browser.</p> </iframe>';
            $o .= '<hr/><h1>Embed code</h1><input style="border:1px solid black;" size="100" type="text" value="' . addslashes(htmlentities($iframe)) . '" />';
        }

        return $o;
    }

    /**
     * @return string
     */
    private function renderUploadForm(): string
    {
        $maxFileSize    = (int) ini_get('upload_max_filesize');
        $uploadCodeForm = '';

        if (UPLOAD_CODE) {
            $uploadCodeForm = '<strong>' . $this->translate(20) . ': </strong><input class="input" type="password" name="upload_code" value="' . $_REQUEST['upload_code'] . '"><div class="clear"></div>';
        }

        return '
		<div class="clear"></div>
		<strong>' . $this->translate(0) . ': ' . $maxFileSize . 'MB / File</strong><br>
		<strong>' . $this->translate(1) . '</strong>
		<br><br>
		<FORM id="form" enctype="multipart/form-data" method="post">
		<div id="formular">
			' . $uploadCodeForm . '
			<strong>' . $this->translate(4) . ': </strong><input class="input" type="file" name="pic[]" multiple><div class="clear"></div>
			<div class="clear"></div><br>
		</div>
			<INPUT class="btn" style="font-size:15px;font-weight:bold;background-color:#74BDDE;padding:3px;" type="submit" id="submit" name="submit" value="' . $this->translate(3) . '" onClick="setTimeout(function(){document.getElementById(\'submit\').disabled = \'disabled\';}, 1);$(\'#movingBallG\').fadeIn()">
			<div id="movingBallG" class="invisible">
				<div class="movingBallLineG"></div>
				<div id="movingBallG_1" class="movingBallG"></div>
			</div>
		</FORM>';
    }

    /**
     * @param array|null $variables
     */
    private function render(array $variables = null)
    {
        if (\is_array($variables)) {
            extract($variables, EXTR_OVERWRITE);
        }

        include BASE_DIR . 'template.php';
    }

    /**
     * @param $data
     */
    private function renderAlbum($data)
    {
        $size    = 300;
        $filters = '';

        if ($data['filter']) {
            $filters = implode('/', $data['filter']) . '/';
        }

        if ($data['size']) {
            $size = $data['size'] . '/';
        } elseif (!$data['responsive']) {
            $size = '300x300/';
        }

        $forcesize = ($data['forcesize'] ? 'forcesize/' : '');

        $content = '';

        foreach ($data['album'] as $hash) {
            $content .= '<a href="' . PATH . $filters . $hash . '"><img class="picture" src="' . PATH . $size . $forcesize . $filters . $hash . '" /></a>';
        }

        if ($data['embed'] === true) {
            include BASE_DIR . 'template_album_embed.php';
        } else {
            include BASE_DIR . 'template_album.php';
        }
    }

    /**
     * @param $data
     *
     * @throws \Exception
     */
    private function renderImage($data)
    {
        $hash = $data['hash'];
        $changecode = null;

        if ($data['changecode']) {
            $changecode = $data['changecode'];
            unset($data['changecode']);
        }

        $base_path = UPLOAD_DIR . $hash . '/';
        $path = $base_path . $hash;
        $type = $this->model->isTypeAllowed($this->getTypeOfFile($path));
        $cached = false;

        //update last_rendered of this hash so we can later
        //sort out old, unused images easier
        @file_put_contents($base_path . 'last_rendered.txt', time());

        $cachename = $this->getCacheName($data);
        $cachepath = $base_path . $cachename;
        if (file_exists($cachepath)) {
            $path = $cachepath;
            $cached = true;
        } elseif (MAX_RESIZED_IMAGES > -1 && $this->countResizedImages($hash) > MAX_RESIZED_IMAGES) {
            // If the number of max resized images is reached, just show the real one.
            $path = BASE_DIR . $hash . '/' . $hash;
        }

        switch ($type) {
            case 'jpg':
                header('Content-type: image/jpeg');
                $im = imagecreatefromjpeg($path);
                if (!$cached) {
                    if ($this->changeCodeExists($changecode)) {
                        $this->changeImage($im, $data);
                        imagejpeg($im, $cachepath, (\defined('JPEG_COMPRESSION') ? JPEG_COMPRESSION : 90));
                    }
                }
                imagejpeg($im);
                break;
            case 'png':
                header('Content-type: image/png');
                $im = imagecreatefrompng($path);
                if (!$cached) {
                    if ($this->changeCodeExists($changecode)) {
                        $this->changeImage($im, $data);
                        imagepng($im, $cachepath, (\defined('PNG_COMPRESSION') ? PNG_COMPRESSION : 6));
                    }
                }
                imagealphablending($im, true);
                imagesavealpha($im, true);
                imagepng($im);
                break;
            case 'gif':
                if ($data['mp4'] || $data['webm'] || $data['ogg']) { //user wants mp4 or webm or ogg
                    $gifpath = $path;
                    $mp4path = $base_path . 'mp4_1.' . $hash; //workaround.. find a better solution!
                    $webmpath = $base_path . 'webm_1.' . $hash;
                    $oggpath = $base_path . 'ogg_1.' . $hash;

                    if (!file_exists($mp4path) && !$data['preview']) { //if mp4 does not exist, create it
                        $this->gifToMP4($gifpath, $mp4path);
                    }

                    if (!file_exists($webmpath) && $data['webm'] && !$data['preview']) {
                        $this->saveAsWebm($gifpath, $webmpath);
                    }

                    if (!file_exists($oggpath) && $data['ogg'] && !$data['preview']) {
                        $this->saveAsOGG($gifpath, $oggpath);
                    }

                    if ($data['raw']) {
                        if ($data['webm']) {
                            $this->serveFile($webmpath, 'video/webm');
                        }
                        if ($data['ogg']) {
                            $this->serveFile($oggpath, 'video/ogg');
                        } else {
                            $this->serveMP4($mp4path);
                        }
                    } elseif ($data['preview']) {
                        if (!file_exists($cachepath)) {
                            $this->saveFirstFrameOfMP4($mp4path, $cachepath);
                        }
                        header('Content-type: image/jpeg');
                        readfile($cachepath);
                    } else {
                        $this->renderMP4($mp4path, $data);
                    }
                } else { //user wants gif
                    if (!$cached && $data['size']) {
                        $this->resizeFFMPEG($data, $cachepath, 'gif');
                    }
                    header('Content-type: image/gif');
                    if (file_exists($cachepath)) {
                        readfile($cachepath);
                    } else {
                        readfile($path);
                    }
                }

                break;
            case 'mp4':
                if (!$cached && !$data['preview']) {
                    $this->resizeFFMPEG($data, $cachepath, 'mp4');
                    $path = $cachepath;
                }

                if (file_exists($cachepath) && filesize($cachepath) === 0) {
                    // If there was an error and the file is 0 bytes, use the original.
                    $cachepath = BASE_DIR . $hash . '/' . $hash;
                }

                if ($data['webm']) {
                    $this->saveAsWebm(BASE_DIR . $hash . '/' . $hash, $cachepath);
                }

                if ($data['ogg']) {
                    $this->saveAsOGG(BASE_DIR . $hash . '/' . $hash, $cachepath);
                }

                if ($data['raw']) {
                    $this->serveMP4($cachepath);
                } elseif ($data['preview']) {
                    if (!file_exists($cachepath)) {
                        $this->saveFirstFrameOfMP4($path, $cachepath);
                    }
                    header('Content-type: image/jpeg');
                    readfile($cachepath);
                } else {
                    $this->renderMP4($path, $data);
                }
                break;
        }

        exit();
    }

    /**
     * @param string $path
     * @param array $data
     */
    private function renderMP4(string $path, array $data)
    {
        $hash = $data['hash'];
        $urldata = $this->getURLInfo($path, true);

        if ($data['size']) {
            $hash = $data['size'] . '/' . $hash;
        }

        $info = $this->getSizeOfMP4($path);
        $width = $info['width'];
        $height = $info['height'];
        $filesize = $urldata['humansize'];

        include BASE_DIR . 'template_mp4.php';
    }

    /**
     * @see From: https://stackoverflow.com/questions/25975943/php-serve-mp4-chrome-provisional-headers-are-shown-request-is-not-finished-ye
     * @param string $filename
     * @param string $mime
     *
     * @throws \Exception
     */
    private function serveFile(string $filename, string $mime = 'application/octet-stream')
    {
        $buffer_size = 8192;
        $expiry = 90; //days

        if (!file_exists($filename)) {
            throw new \Exception('File not found: ' . $filename);
        }
        if (!is_readable($filename)) {
            throw new \Exception('File not readable: ' . $filename);
        }

        header_remove('Cache-Control');
        header_remove('Pragma');

        $byte_offset = 0;
        $filesize_bytes = $filesize_original = filesize($filename);

        header('Accept-Ranges: bytes', true);
        header('Content-Type: ' . $mime, true);
        header('Content-Disposition: inline;');

        // Content-Range header for byte offsets
        if (isset($_SERVER['HTTP_RANGE']) && preg_match('%bytes=(\d+)-(\d+)?%i', $_SERVER['HTTP_RANGE'], $match)) {
            $byte_offset = (int) $match[1];//Offset signifies where we should begin to read the file
            if (isset($match[2])) {//Length is for how long we should read the file according to the browser, and can never go beyond the file size
                $filesize_bytes = min((int) $match[2], $filesize_bytes - $byte_offset);
            }
            header('HTTP/1.1 206 Partial content');
            header(sprintf('Content-Range: bytes %d-%d/%d', $byte_offset, $filesize_bytes - 1, $filesize_original)); // Decrease by 1 on byte-length since this definition is zero-based index of bytes being sent
        }

        $byte_range = $filesize_bytes - $byte_offset;

        header('Content-Length: ' . $byte_range);
        header('Expires: ' . (new \DateTime())->modify('+' . $expiry . ' days')->format('D, d M Y H:i:s') . ' GMT');

        $bytes_remaining = $byte_range;

        $handle = fopen($filename, 'rb');
        if (!$handle) {
            throw new \Exception('Could not get handle for file: ' .  $filename);
        }
        if (fseek($handle, $byte_offset, SEEK_SET) === -1) {
            throw new \Exception('Could not seek to byte offset %d', $byte_offset);
        }

        while ($bytes_remaining > 0) {
            $chunksize_requested = min($buffer_size, $bytes_remaining);
            $buffer = fread($handle, $chunksize_requested);
            $chunksize_real = strlen($buffer);

            if ($chunksize_real === 0) {
                break;
            }

            $bytes_remaining -= $chunksize_real;
            echo $buffer;
            flush();
        }
    }

    /**
     * @see Via gist: https://gist.github.com/codler/3906826
     *
     * @param string $path
     */
    private function serveMP4(string $path)
    {
        if ($fp = fopen($path, 'rb')) {
            $size = filesize($path);
            $length = $size;
            $start = 0;
            $end = $size - 1;
            header('Content-type: video/mp4');
            header("Accept-Ranges: 0-$length");

            if (isset($_SERVER['HTTP_RANGE'])) {
                $c_end = $end;
                list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

                if (strpos($range, ',') !== false) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }

                if ($range === '-') {
                    $c_start = $size - substr($range, 1);
                } else {
                    $range = explode('-', $range);
                    $c_start = $range[0];
                    $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                }

                $c_end = ($c_end > $end) ? $end : $c_end;

                if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                    header('HTTP/1.1 416 Requested Range Not Satisfiable');
                    header("Content-Range: bytes $start-$end/$size");
                    exit;
                }

                $start = $c_start;
                $end = $c_end;
                $length = $end - $start + 1;
                fseek($fp, $start);
                header('HTTP/1.1 206 Partial Content');
            }

            header("Content-Range: bytes $start-$end/$size");
            header('Content-Length: ' . $length);

            $buffer = 1024 * 8;

            while (!feof($fp) && ($p = ftell($fp)) <= $end) {
                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(0);
                echo fread($fp, $buffer);
                flush();
            }

            fclose($fp);
            exit();
        }
        die('file not found');
    }

    /**
     * @param $im
     * @param string $direction
     */
    private function rotate(&$im, string $direction)
    {
        switch ($direction) {
            case 'upside':
                $angle = 180;
                break;
            case 'left':
                $angle = 90;
                break;
            case 'right':
                $angle = -90;
                break;
            default:
                $angle = 0;
                break;
        }

        $im = FilterFactory::getFilter('rotate')->setSettings(['angle' => $angle])->apply()->getImage();
    }

    /**
     * @param $img
     * @param $size
     */
    private function forceResize(&$img, $size)
    {
        $sd = $this->sizeStringToWidthHeight($size);
        $maxWidth  = $sd['width'];
        $maxHeight = $sd['height'];

        $width = imagesx($img);
        $height = imagesy($img);

        $maxWidth = ($maxWidth > $width ? $width : $maxWidth);
        $maxHeight = ($maxHeight > $height ? $height : $maxHeight);


        $dstImg = imagecreatetruecolor($maxWidth, $maxHeight);
        $srcImg = $img;

        $palsize = imagecolorstotal($img);

        for ($i = 0; $i < $palsize; $i++) {
            $colors = imagecolorsforindex($img, $i);
            imagecolorallocate($dstImg, $colors['red'], $colors['green'], $colors['blue']);
        }

        imagefill($dstImg, 0, 0, IMG_COLOR_TRANSPARENT);
        imagesavealpha($dstImg, true);
        imagealphablending($dstImg, true);

        $newWidth = $height * $maxWidth / $maxHeight;
        $newHeight = $width * $maxHeight / $maxWidth;

        // If the new width is greater than the actual width of the image,
        // then the height is too large and the rest cut off, or vice versa.
        if ($newWidth > $width) {
            //cut point by height
            $hPoint = (($height - $newHeight) / 2);
            //copy image
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, $hPoint, $maxWidth, $maxHeight, $width, $newHeight);
        } else {
            //cut point by width
            $wPoint = (($width - $newWidth) / 2);
            imagecopyresampled($dstImg, $srcImg, 0, 0, $wPoint, 0, $maxWidth, $maxHeight, $newWidth, $height);
        }

        $img = $dstImg;
    }

    /**
     * From: https://stackoverflow.com/questions/4590441/php-thumbnail-image-resizing-with-proportions
     *
     * @param $img
     * @param int $size
     */
    private function resize(&$img, $size)
    {
        $sd = $this->sizeStringToWidthHeight($size);
        $maxWidth  = $sd['width'];
        $maxHeight = $sd['height'];

        $width = imagesx($img);
        $height = imagesy($img);

        if (!ALLOW_BLOATING) {
            if ($maxWidth > $width) {
                $maxWidth = $width;
            }
            if ($maxHeight > $height) {
                $maxHeight = $height;
            }
        }

        if ($height > $width) {
            $ratio = $maxHeight / $height;
            $newHeight = $maxHeight;
            $newWidth = $width * $ratio;
        } else {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = $height * $ratio;
        }

        $newImg = imagecreatetruecolor($newWidth, $newHeight);

        $palSize = imagecolorstotal($img);

        for ($i = 0; $i < $palSize; $i++) {
            $colors = imagecolorsforindex($img, $i);
            imagecolorallocate($newImg, $colors['red'], $colors['green'], $colors['blue']);
        }

        imagefill($newImg, 0, 0, IMG_COLOR_TRANSPARENT);
        imagesavealpha($newImg, true);
        imagealphablending($newImg, true);

        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $img = $newImg;
    }

    /**
     * @param $im
     * @param array $vars
     *
     * @return resource GD image resource
     */
    private function filter(&$im, $vars)
    {
        foreach ($vars as $var) {
            $filterName  = $var;
            $filterValue = null;
            $params      = [];

            if (strpos($var, '_')) {
                list($filterName, $filterValue) = explode('_', $var);
            }

            if ($filterValue !== null) {
                $params = [
                    'brightness' => $filterValue,
                    'blur'       => $filterValue,
                    'pixelate'   => $filterValue,
                    'smooth'     => $filterValue,
                    'angle'      => $filterValue,
                ];
            }

            $im = FilterFactory::getFilter($filterName)
                ->setImage($im)
                ->setSettings($params)
                ->apply()
                ->getImage();
        }

        return $im;
    }

    /**
     * @param $im
     * @param array $data
     */
    private function changeImage(&$im, array $data)
    {
        foreach ($data as $action => $val) {
            switch ($action) {
                case 'rotate':
                    $this->rotate($im, $val);
                    break;
                case 'size':
                    $data['forcesize'] === true ? $this->forceResize($im, $val) : $this->resize($im, $val);
                    break;
                case 'filter':
                    $this->filter($im, $val);
                    break;
            }
        }
    }

    /**
     * @param array $data
     * @param string $cachepath
     * @param string $type
     *
     * @return string
     */
    private function resizeFFMPEG(array $data, string $cachepath, string $type = 'mp4'): string
    {
        $file = UPLOAD_DIR . $data['hash'] . '/' . $data['hash'];
        $file = escapeshellarg($file);
        $bin  = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $size = $data['size'];

        if (!$size) {
            return $file;
        }

        $sd        = $this->sizeStringToWidthHeight($size);
        $maxwidth  = $sd['width'];
        $addition  = '';

        switch ($type) {
            case 'mp4':
                $addition = '-c:v libx264 -profile:v baseline -level 3.0 -pix_fmt yuv420p';
                break;
        }

        $maxheight = 'trunc(ow/a/2)*2';

        $cmd = "$bin -i $file -y -vf scale=\"$maxwidth:$maxheight\" $addition -f $type $cachepath";

        system($cmd);

        return $cachepath;
    }

    /**
     * @param string $gifpath
     * @param string $target
     *
     * @return string
     */
    private function gifToMP4(string $gifpath, string $target): string
    {
        $bin = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $file = escapeshellarg($gifpath);

        if (!file_exists($target)) { //simple caching.. have to think of something better
            $cmd = "$bin -f gif -y -i $file -c:v libx264 -f mp4 $target";
            system($cmd);
        }

        return $target;
    }

    /**
     * @param string $source
     * @param string $target
     */
    private function saveAsOGG(string $source, string $target)
    {
        $bin = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $h265 = "$bin -y -i $source -vcodec libtheora -acodec libvorbis -qp 0 -f ogg $target";
        system($h265);
    }

    /**
     * @param string $source
     * @param string $target
     */
    private function saveAsWebm(string $source, string $target)
    {
        $bin = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $source = escapeshellarg($source);
        $target = escapeshellarg($target);
        $webm = "$bin -y -i $source -vcodec libvpx -acodec libvorbis -aq 5 -ac 2 -qmax 25 -f webm $target";
        system($webm);
    }

    /**
     * @param string $path
     * @param string $target
     */
    private function saveFirstFrameOfMP4(string $path, string $target)
    {
        $bin = escapeshellcmd(BASE_DIR . 'bin/ffmpeg');
        $file = escapeshellarg($path);
        $cmd = "$bin -y -i $file -vframes 1 -f image2 $target";

        system($cmd);
    }

    /**
     * @param string|int $size
     *
     * @return array|bool
     */
    private function sizeStringToWidthHeight($size)
    {
        if (!$size || !$this->isSize($size)) {
            return false;
        }

        $newSize = $size;

        if (!is_numeric($size)) {
            $newSize = explode('x', $size);
        }

        if (\is_array($newSize)) {
            list($maxWidth, $maxHeight) = $newSize;
        } else {
            $maxWidth  = $newSize;
            $maxHeight = $newSize;
        }

        return [
            'width'  => $maxWidth,
            'height' => $maxHeight,
        ];
    }

    /**
     * @param string|null $code
     *
     * @return bool
     */
    public function changeCodeExists(string $code = null): bool
    {
        if (!IMAGE_CHANGE_CODE) {
            return true;
        }

        if (strpos(IMAGE_CHANGE_CODE, ';')) {
            $codes = explode(';', IMAGE_CHANGE_CODE);

            foreach ($codes as $ucode) {
                if ($code === $ucode) {
                    return true;
                }
            }
        }

        return $code === IMAGE_CHANGE_CODE;
    }

    /**
     * @param string $hash
     *
     * @return int
     */
    public function countResizedImages(string $hash): int
    {
        $fi = new \FilesystemIterator(UPLOAD_DIR . $hash . '/', \FilesystemIterator::SKIP_DOTS);

        return iterator_count($fi);
    }
}
