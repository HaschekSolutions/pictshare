<?php

declare(strict_types=1);

namespace PictShare\Classes;

use PictShare\Classes\StorageProviders\StorageProviderInterface;

/**
 * @TODO Inject new StorageProviders at runtime for unit tests?
 */
class StorageProviderFactory
{
    const BACKBLAZE_PROVIDER = 'Backblaze';
    const LOCAL_PROVIDER     = 'Local';

    /**
     * @param string $providerName
     *
     * @return StorageProviderInterface
     */
    public static function getStorageProvider(string $providerName): StorageProviderInterface
    {
        $className = __NAMESPACE__ . '\\StorageProviders\\' . $providerName . 'StorageProvider';

        return new $className();
    }
}
