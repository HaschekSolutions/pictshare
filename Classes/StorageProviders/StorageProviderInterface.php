<?php

declare(strict_types=1);

namespace PictShare\Classes\StorageProviders;

interface StorageProviderInterface
{
    /**
     * Get a file.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function get(string $fileName): bool;

    /**
     * Save a file.
     *
     * @param string $fileName
     */
    public function save(string $fileName);

    /**
     * Delete a file.
     *
     * @param string $fileName
     */
    public function delete(string $fileName);
}
