<?php

declare(strict_types=1);

namespace PictShare\Models;

class BaseModel
{
    /**
     * @param string|null $hash
     *
     * @return bool
     */
    public function isImage(string $hash = null): bool
    {
        if (!$hash) {
            return false;
        }

        return $this->hashExists($hash);
    }

    /**
     * @param string $type
     *
     * @return bool|string
     */
    public function isTypeAllowed(string $type)
    {
        switch ($type) {
            case 'image/png':
                return 'png';
            case 'image/x-png':
                return 'png';
            case 'x-png':
                return 'png';
            case 'png':
                return 'png';

            case 'image/jpeg':
                return 'jpg';
            case 'jpeg':
                return 'jpg';
            case 'pjpeg':
                return 'jpg';

            case 'image/gif':
                return 'gif';
            case 'gif':
                return 'gif';

            case 'mp4':
                return 'mp4';

            default:
                return false;
        }
    }

    /**
     * @param string $hash
     *
     * @return bool
     */
    public function hashExists(string $hash): bool
    {
        return is_dir(UPLOAD_DIR . $hash);
    }
}
