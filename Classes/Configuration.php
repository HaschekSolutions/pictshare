<?php

declare(strict_types=1);

namespace PictShare\Classes;

use PictShare\Classes\Exceptions\ConfigDefaultMissingException;

class Configuration
{
    const BACKBLAZE              = 'BACKBLAZE';
    const BACKBLAZE_ID           = 'BACKBLAZE_ID';
    const BACKBLAZE_KEY          = 'BACKBLAZE_KEY';
    const BACKBLAZE_BUCKET_ID    = 'BACKBLAZE_BUCKET_ID';
    const BACKBLAZE_BUCKET_NAME  = 'BACKBLAZE_BUCKET_NAME';
    const BACKBLAZE_AUTODOWNLOAD = 'BACKBLAZE_AUTODOWNLOAD';
    const BACKBLAZE_AUTOUPLOAD   = 'BACKBLAZE_AUTOUPLOAD';
    const BACKBLAZE_AUTODELETE   = 'BACKBLAZE_AUTODELETE';

    const DEFAULTS = [
        self::BACKBLAZE              => false,
        self::BACKBLAZE_ID           => null,
        self::BACKBLAZE_KEY          => null,
        self::BACKBLAZE_BUCKET_ID    => null,
        self::BACKBLAZE_BUCKET_NAME  => null,
        self::BACKBLAZE_AUTODOWNLOAD => false,
        self::BACKBLAZE_AUTOUPLOAD   => false,
        self::BACKBLAZE_AUTODELETE   => false,
    ];


    /**
     * @param string $configKeyName
     *
     * @return mixed|null
     */
    public static function getValue(string $configKeyName)
    {
        if (\defined($configKeyName)) {
            return \constant($configKeyName);
        }

        return static::getDefault($configKeyName);
    }

    /**
     * @return bool
     */
    public static function isBackblazeEnabled(): bool
    {
        return \defined(static::BACKBLAZE_ID)
            && \defined(static::BACKBLAZE_KEY)
            && \defined(static::BACKBLAZE_BUCKET_ID)
            && \defined(static::BACKBLAZE)
            && static::getValue(static::BACKBLAZE) === true;
    }

    /**
     * @return bool
     */
    public static function isBackblazeAutoDownloadEnabled(): bool
    {
        return static::isBackblazeEnabled()
            && static::getValue(static::BACKBLAZE_AUTODOWNLOAD) === true;
    }

    /**
     * @return bool
     */
    public static function isBackblazeAutoUploadEnabled(): bool
    {
        return static::isBackblazeEnabled()
            && static::getValue(static::BACKBLAZE_AUTOUPLOAD) === true;
    }

    /**
     * @return bool
     */
    public static function isBackblazeAutoDeleteEnabled(): bool
    {
        return static::isBackblazeEnabled()
            && static::getValue(static::BACKBLAZE_AUTODELETE) === true;
    }

    /**
     * @param string $configKeyName
     *
     * @return mixed
     *
     * @throws ConfigDefaultMissingException
     */
    private static function getDefault(string $configKeyName)
    {
        if (\array_key_exists($configKeyName, static::DEFAULTS)) {
            return static::DEFAULTS[$configKeyName];
        }

        throw new ConfigDefaultMissingException(
            'Missing default value for config option ' . $configKeyName
        );
    }
}
