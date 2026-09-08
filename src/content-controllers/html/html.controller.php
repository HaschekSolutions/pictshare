<?php

/**
 * Hosts raw, self-contained HTML pages (inline CSS/JS allowed) verbatim on this
 * domain - one pagers / demos for clients. This is a same-origin XSS-as-a-service
 * feature by design: anyone holding HTML_UPLOAD_CODE can serve arbitrary script on
 * this domain, including against any admin session cookie a browser happens to be
 * carrying (see rtfm/CONFIG.md). Off unless an admin explicitly configures both
 * HTML_HOSTING_ENABLED and HTML_UPLOAD_CODE - never enabled by CONTENTCONTROLLERS
 * alone. Deliberately no CSP restricting script: that would defeat the purpose of
 * hosting live demos.
 */
class HtmlController implements ContentController
{
    public const ctype = 'static';

    public $mimes = [];

    public function __construct()
    {
        if (self::isEnabled())
            $this->mimes = ['text/html'];
    }

    /** Pure form of the enabled check, so it's testable without redefining constants. */
    public static function computeIsEnabled(bool $flagSet, string $code): bool
    {
        return $flagSet && $code !== '';
    }

    private static function isEnabled(): bool
    {
        return self::computeIsEnabled(
            defined('HTML_HOSTING_ENABLED') && HTML_HOSTING_ENABLED === true,
            defined('HTML_UPLOAD_CODE') ? HTML_UPLOAD_CODE : ''
        );
    }

    /** Pure form of the upload-gate check, so it's testable without redefining constants. */
    public static function checkUploadCode(?string $provided, bool $enabled, string $requiredCode): bool
    {
        if (!$enabled || $requiredCode === '') return false;
        return $provided !== null && hash_equals($requiredCode, $provided);
    }

    //returns all extensions registered by this type of content
    public function getRegisteredExtensions(){ return self::isEnabled() ? array('html','htm') : array(); }

    /**
     * Optional hook api.class.php's handleFile() calls (via method_exists) before its
     * sha1-dedup shortcut, so that shortcut can't hand back an already-stored hash
     * without this controller's own token check ever running.
     */
    public function checkUploadAllowed(): bool
    {
        return self::checkUploadCode(
            $_REQUEST['htmluploadcode'] ?? null,
            self::isEnabled(),
            defined('HTML_UPLOAD_CODE') ? HTML_UPLOAD_CODE : ''
        );
    }

    public function handleUpload($tmpfile,$hash=false,$passthrough=false)
    {
        if (!$this->checkUploadAllowed())
            return array('status'=>'err','reason'=>'HTML hosting is disabled or an incorrect html upload code was provided');

        if($hash===false)
        {
            $hash = getNewHash('html',6);
        }
        else
        {
            if(!endswith($hash,'.html'))
                $hash.='.html';
            if(isHashTaken($hash))
                return array('status'=>'err','hash'=>$hash,'reason'=>'Custom hash already exists');
        }

        if($passthrough===false)
            storeFile($tmpfile,$hash,true);

        return array('status'=>'ok','hash'=>$hash,'url'=>getURL().$hash);
    }

    public function handleHash($hash,$url,$path=false)
    {
        if(!self::isEnabled())
        {
            http_response_code(404);
            return;
        }

        if($path===false)
            $path = getDataDir().DS.$hash.DS.$hash;

        header('X-Content-Type-Options: nosniff');

        if(in_array('download',$url))
        {
            if (file_exists($path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="'.basename($path).'"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                serveFile($path);
                exit;
            }
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        serveFile($path);
    }
}
