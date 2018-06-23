<?php

declare(strict_types=1);

namespace PictShare\Classes\StorageProviders;

/**
 * @TODO Implement this. We can use this provider to handle all local filesystem actions.
 */
class LocalStorageProvider implements StorageProviderInterface
{
    /**
     * @inheritdoc
     *
     * @throws \RuntimeException
     */
    final public function get(string $fileName): bool
    {
        throw new \RuntimeException(__METHOD__ . ' not implemented.');
    }

    /**
     * @inheritdoc
     *
     * @throws \RuntimeException
     */
    final public function save(string $fileName)
    {
        throw new \RuntimeException(__METHOD__ . ' not implemented.');
    }

    /**
     * @inheritdoc
     *
     * @throws \RuntimeException
     */
    final public function delete(string $fileName)
    {
        throw new \RuntimeException(__METHOD__ . ' not implemented.');
    }
}
