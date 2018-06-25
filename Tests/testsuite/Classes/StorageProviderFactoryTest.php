<?php

declare(strict_types=1);

namespace PictShare\Tests;

use PHPUnit\Framework\TestCase;
use PictShare\Classes\StorageProviderFactory;
use PictShare\Classes\StorageProviders\BackblazeStorageProvider;
use PictShare\Classes\StorageProviders\LocalStorageProvider;

class StorageProviderFactoryTest extends TestCase
{
    public function testFactory()
    {
        $storageProviderFactory = new StorageProviderFactory();

        $local = $storageProviderFactory::getStorageProvider(
            StorageProviderFactory::LOCAL_PROVIDER
        );

        self::assertEquals(LocalStorageProvider::class, \get_class($local));

        $backblaze = $storageProviderFactory::getStorageProvider(
            StorageProviderFactory::BACKBLAZE_PROVIDER
        );

        self::assertEquals(BackblazeStorageProvider::class, \get_class($backblaze));
    }
}